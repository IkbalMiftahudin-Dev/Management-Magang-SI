<?php

namespace Tests\Feature;

use App\Enums\JenisProgramMagang;
use App\Enums\ModeKegiatan;
use App\Enums\StatusPendataan;
use App\Models\BerkasAkhir;
use App\Models\Dosen;
use App\Models\KonversiMataKuliah;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\PendataanMagang;
use App\Models\SsoSession;
use App\Models\User;
use App\Services\SsoSessionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PendataanMagangFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-M02 (Pendataan): Complete 4-step wizard stores private LOA, syncs BerkasAkhir slot 01, maps courses, locks editing after submit, and prevents cross-student IDOR.
     */
    public function test_student_can_complete_4_step_pendataan_wizard_and_sync_loa_securely(): void
    {
        Storage::fake('local');

        $userA = User::factory()->mahasiswa()->create(['name' => 'Mohamad Firdiansyah']);
        $mahasiswaA = Mahasiswa::factory()->create(['user_id' => $userA->id, 'nim' => '2402041']);
        $sessionA = $this->createSsoSession($userA);

        $pembimbing = Dosen::factory()->create();
        $pengampu1 = Dosen::factory()->create();
        $pengampu2 = Dosen::factory()->create();

        $mk1 = MataKuliah::factory()->create(['kode_mk' => 'IF231', 'sks' => 3, 'dosen_pengampu_id' => $pengampu1->id]);
        $mk2 = MataKuliah::factory()->create(['kode_mk' => 'IF352', 'sks' => 3, 'dosen_pengampu_id' => $pengampu2->id]);

        // Step 1: Data Instansi
        $step1Response = $this->actingAs($userA)
            ->withSession($sessionA)
            ->postJson(route('mahasiswa.pendataan.step1'), [
                'nama_instansi' => 'Telkom Indonesia',
                'posisi' => 'UI/UX Designer Intern',
                'alamat_instansi' => 'Jl. Japati No. 1, Bandung',
                'kontak_instansi' => 'talent@telkom.co.id',
                'semester_akademik' => 'Semester Genap 2027/2028',
            ]);

        $step1Response->assertCreated()
            ->assertJsonPath('next_step', 2)
            ->assertJsonPath('data.status_pendataan', StatusPendataan::Draft->value);

        // Step 2: Reject invalid file type (BVA/EP check) & accept valid LOA PDF
        $this->actingAs($userA)
            ->withSession($sessionA)
            ->postJson(route('mahasiswa.pendataan.step2'), [
                'jenis_program' => JenisProgramMagang::Msib->value,
                'dosen_pembimbing_id' => $pembimbing->id,
                'mode_kegiatan' => ModeKegiatan::Hybrid->value,
                'tanggal_mulai' => '2026-04-02',
                'tanggal_selesai' => '2026-09-17',
                'loa' => UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['loa']);

        $loaFile = UploadedFile::fake()->create('loa-telkom.pdf', 512, 'application/pdf');

        $step2Response = $this->actingAs($userA)
            ->withSession($sessionA)
            ->postJson(route('mahasiswa.pendataan.step2'), [
                'jenis_program' => JenisProgramMagang::Msib->value,
                'dosen_pembimbing_id' => $pembimbing->id,
                'mode_kegiatan' => ModeKegiatan::Hybrid->value,
                'tanggal_mulai' => '2026-04-02',
                'tanggal_selesai' => '2026-09-17',
                'loa' => $loaFile,
            ]);

        $step2Response->assertOk()->assertJsonPath('next_step', 3);

        $pendataan = PendataanMagang::query()->where('mahasiswa_id', $mahasiswaA->id)->firstOrFail();
        $this->assertNotNull($pendataan->loa_path);
        Storage::disk('local')->assertExists($pendataan->loa_path);

        // Verify LOA auto-synced into BerkasAkhir slot 01.
        $berkas = BerkasAkhir::query()->where('pendataan_magang_id', $pendataan->id)->firstOrFail();
        $this->assertSame($pendataan->loa_path, $berkas->loa_path);

        // Step 3: Pemetaan Konversi Mata Kuliah
        $this->actingAs($userA)
            ->withSession($sessionA)
            ->postJson(route('mahasiswa.pendataan.step3'), [
                'mata_kuliah_ids' => [$mk1->id, $mk2->id],
            ])
            ->assertOk()
            ->assertJsonPath('next_step', 4);

        $this->assertSame(2, KonversiMataKuliah::query()->where('pendataan_magang_id', $pendataan->id)->count());
        $this->assertDatabaseHas('konversi_mata_kuliahs', [
            'pendataan_magang_id' => $pendataan->id,
            'mata_kuliah_id' => $mk2->id,
            'dosen_penilai_id' => $pengampu2->id,
            'sks_konversi' => 3,
        ]);

        // Step 4: Submit final pendataan
        $this->actingAs($userA)
            ->withSession($sessionA)
            ->postJson(route('mahasiswa.pendataan.step4'), [
                'pernyataan_disetujui' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.status_pendataan', StatusPendataan::MenungguVerifikasi->value);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $userA->id,
            'action' => 'pendataan.submitted',
            'subject_id' => $pendataan->id,
        ]);

        // Once submitted (menunggu_verifikasi), student cannot edit Step 1 anymore.
        $this->actingAs($userA)
            ->withSession($sessionA)
            ->postJson(route('mahasiswa.pendataan.step1'), [
                'nama_instansi' => 'Ubah Diam-Diam',
                'posisi' => 'Hacker',
                'alamat_instansi' => 'Alamat',
                'kontak_instansi' => 'a@b.com',
            ])
            ->assertForbidden();

        // Owner CAN stream their own private LOA.
        $this->actingAs($userA)
            ->withSession($sessionA)
            ->get(route('files.pendataan.slot', ['pendataan' => $pendataan, 'slot' => 'loa']))
            ->assertOk();

        // Anti-IDOR: Another student (Mahasiswa B) is BLOCKED (403) from streaming Mahasiswa A's LOA.
        $userB = User::factory()->mahasiswa()->create();
        Mahasiswa::factory()->create(['user_id' => $userB->id]);

        $this->actingAs($userB)
            ->withSession($this->createSsoSession($userB))
            ->get(route('files.pendataan.slot', ['pendataan' => $pendataan, 'slot' => 'loa']))
            ->assertForbidden();

        // Assigned Dosen Pengampu MK CAN view/stream Mahasiswa A's LOA for evaluation context.
        $this->actingAs($pengampu2->user)
            ->withSession($this->createSsoSession($pengampu2->user))
            ->get(route('files.pendataan.slot', ['pendataan' => $pendataan, 'slot' => 'loa']))
            ->assertOk();
    }

    public function test_student_can_re_edit_pendataan_when_status_is_perlu_revisi(): void
    {
        $user = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create(['user_id' => $user->id]);
        $session = $this->createSsoSession($user);

        PendataanMagang::factory()->create([
            'mahasiswa_id' => $mahasiswa->id,
            'status_pendataan' => StatusPendataan::PerluRevisi,
            'catatan_verifikasi' => 'Mohon perbaiki alamat instansi.',
        ]);

        $this->actingAs($user)
            ->withSession($session)
            ->postJson(route('mahasiswa.pendataan.step1'), [
                'nama_instansi' => 'Telkom Indonesia Tbk',
                'posisi' => 'UI/UX Designer Intern',
                'alamat_instansi' => 'Jl. Japati No. 1, Kota Bandung, Jawa Barat',
                'kontak_instansi' => 'talent@telkom.co.id',
            ])
            ->assertOk()
            ->assertJsonPath('data.nama_instansi', 'Telkom Indonesia Tbk');
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
