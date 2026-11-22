<?php

namespace Tests\Feature;

use App\Enums\StatusBerkasAkhir;
use App\Enums\StatusValidasiLogbook;
use App\Enums\TipeLogbook;
use App\Models\Dosen;
use App\Models\Logbook;
use App\Models\Mahasiswa;
use App\Models\PendataanMagang;
use App\Models\SsoSession;
use App\Models\User;
use App\Services\SsoSessionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LogbookDanBerkasAkhirFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-M03: Mahasiswa creates logbook with private photo evidence; assigned Dosen Pembimbing UPI validates it; validated logbook becomes locked against student edits.
     */
    public function test_logbook_creation_private_photo_validation_and_locking(): void
    {
        Storage::fake('local');

        $mhsUser = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create(['user_id' => $mhsUser->id]);
        $mhsSession = $this->createSsoSession($mhsUser);

        $pembimbingUser = User::factory()->dosen()->create();
        $pembimbing = Dosen::factory()->create(['user_id' => $pembimbingUser->id]);
        $pembimbingSession = $this->createSsoSession($pembimbingUser);

        $otherDosenUser = User::factory()->dosen()->create();
        Dosen::factory()->create(['user_id' => $otherDosenUser->id]);
        $otherDosenSession = $this->createSsoSession($otherDosenUser);

        $pendataan = PendataanMagang::factory()->verified()->create([
            'mahasiswa_id' => $mahasiswa->id,
            'dosen_pembimbing_id' => $pembimbing->id,
        ]);

        // Reject non-image file (EP check).
        $this->actingAs($mhsUser)
            ->withSession($mhsSession)
            ->postJson(route('mahasiswa.logbook.store'), [
                'tipe_pengisian' => TipeLogbook::Mingguan->value,
                'tanggal' => '2026-06-16',
                'minggu_ke' => 7,
                'deskripsi_kegiatan' => 'Usability testing prototype dashboard dan menyusun temuan utama.',
                'foto_bukti' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['foto_bukti']);

        // Store valid logbook with JPG evidence.
        $createResponse = $this->actingAs($mhsUser)
            ->withSession($mhsSession)
            ->postJson(route('mahasiswa.logbook.store'), [
                'tipe_pengisian' => TipeLogbook::Mingguan->value,
                'tanggal' => '2026-06-16',
                'minggu_ke' => 7,
                'deskripsi_kegiatan' => 'Usability testing prototype dashboard dan menyusun temuan utama.',
                'foto_bukti' => UploadedFile::fake()->image('bukti-minggu-7.jpg', 800, 600),
            ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.status_validasi', StatusValidasiLogbook::Menunggu->value);

        $logbook = Logbook::query()->findOrFail($createResponse->json('data.id'));
        Storage::disk('local')->assertExists($logbook->foto_bukti_path);

        // Owner & Pembimbing can stream private photo; unrelated Dosen cannot.
        $this->actingAs($mhsUser)->withSession($mhsSession)->get(route('files.logbook', $logbook))->assertOk();
        $this->actingAs($pembimbingUser)->withSession($pembimbingSession)->get(route('files.logbook', $logbook))->assertOk();
        $this->actingAs($otherDosenUser)->withSession($otherDosenSession)->get(route('files.logbook', $logbook))->assertForbidden();

        // Unrelated Dosen cannot validate this student's logbook.
        $this->actingAs($otherDosenUser)
            ->withSession($otherDosenSession)
            ->patchJson(route('dosen.bimbingan.logbook.validate', $logbook), [
                'status_validasi' => StatusValidasiLogbook::Tervalidasi->value,
            ])
            ->assertForbidden();

        // Assigned Dosen Pembimbing UPI validates the logbook.
        $this->actingAs($pembimbingUser)
            ->withSession($pembimbingSession)
            ->patchJson(route('dosen.bimbingan.logbook.validate', $logbook), [
                'status_validasi' => StatusValidasiLogbook::Tervalidasi->value,
                'catatan_dosen' => 'Progres sangat baik.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status_validasi', StatusValidasiLogbook::Tervalidasi->value);

        // Once validated, student can no longer edit or delete that entry.
        $this->actingAs($mhsUser)
            ->withSession($mhsSession)
            ->putJson(route('mahasiswa.logbook.update', $logbook), [
                'tipe_pengisian' => TipeLogbook::Mingguan->value,
                'tanggal' => '2026-06-16',
                'minggu_ke' => 7,
                'deskripsi_kegiatan' => 'Mencoba ubah setelah divalidasi dosen.',
            ])
            ->assertForbidden();

        $this->actingAs($mhsUser)
            ->withSession($mhsSession)
            ->deleteJson(route('mahasiswa.logbook.destroy', $logbook))
            ->assertForbidden();
    }

    /**
     * TC-M04: One-Stop Submission validates PDF ≤ 10MB per slot, blocks submit before 5 mandatory documents exist, and succeeds without optional Sertifikat.
     */
    public function test_one_stop_submission_enforces_5_mandatory_documents_and_1_optional_certificate(): void
    {
        Storage::fake('local');

        $mhsUser = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create(['user_id' => $mhsUser->id]);
        $mhsSession = $this->createSsoSession($mhsUser);

        PendataanMagang::factory()->verified()->create([
            'mahasiswa_id' => $mahasiswa->id,
            'loa_path' => null,
            'loa_uploaded_at' => null,
        ]);

        // Reject non-PDF file on berkas akhir slot.
        $this->actingAs($mhsUser)
            ->withSession($mhsSession)
            ->postJson(route('mahasiswa.berkas-akhir.upload', ['slot' => 'loa']), [
                'file' => UploadedFile::fake()->image('loa.png'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);

        // Upload only 3 mandatory files first -> submit must fail with 422.
        foreach (['loa', 'rubrik_konversi', 'transkrip_instansi'] as $slot) {
            $this->actingAs($mhsUser)
                ->withSession($mhsSession)
                ->postJson(route('mahasiswa.berkas-akhir.upload', ['slot' => $slot]), [
                    'file' => UploadedFile::fake()->create($slot.'.pdf', 300, 'application/pdf'),
                ])
                ->assertOk();
        }

        $this->actingAs($mhsUser)
            ->withSession($mhsSession)
            ->postJson(route('mahasiswa.berkas-akhir.submit'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['berkas']);

        // Upload remaining 2 mandatory files (total 5/5 mandatory, sertifikat still empty/optional).
        foreach (['laporan_akhir', 'draf_pks'] as $slot) {
            $this->actingAs($mhsUser)
                ->withSession($mhsSession)
                ->postJson(route('mahasiswa.berkas-akhir.upload', ['slot' => $slot]), [
                    'file' => UploadedFile::fake()->create($slot.'.pdf', 400, 'application/pdf'),
                ])
                ->assertOk();
        }

        $this->actingAs($mhsUser)
            ->withSession($mhsSession)
            ->postJson(route('mahasiswa.berkas-akhir.submit'))
            ->assertOk()
            ->assertJsonPath('data.status_kelengkapan', StatusBerkasAkhir::SiapDinilai->value);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $mhsUser->id,
            'action' => 'berkas_akhir.submitted',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function createSsoSession(User $user): array
    {
        $ssoSession = SsoSession::factory()->create(['user_id' => $user->id]);

        return [SsoSessionManager::SESSION_TOKEN_KEY => $ssoSession->session_id];
    }
}
