<?php

namespace Tests\Feature;

use App\Enums\JenisSk;
use App\Enums\StatusPendataan;
use App\Enums\StatusPengesahanSk;
use App\Models\DokumenSk;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\PendataanMagang;
use App\Models\SsoSession;
use App\Models\User;
use App\Services\SsoSessionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Support\InteractsWithFakeSso;
use Tests\TestCase;

class VerifikasiDanSkFeatureTest extends TestCase
{
    use InteractsWithFakeSso;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureTestSso();
    }

    /**
     * TC-M02 End-to-End: Staff_Kaprodi verifies, plots Pembimbing Lapangan, generates draft SK PDFs;
     * Kaprodi exclusively approves SK with live /userinfo check; Mahasiswa can only download after approval.
     */
    public function test_staff_verifies_generates_draft_sk_pdf_and_kaprodi_exclusively_approves(): void
    {
        Storage::fake('local');

        $mhsUser = User::factory()->mahasiswa()->create(['name' => 'Ikbal Miftahudin']);
        $mahasiswa = Mahasiswa::factory()->create(['user_id' => $mhsUser->id, 'nim' => '2401426', 'nama' => 'Ikbal Miftahudin']);
        $pembimbing = Dosen::factory()->create(['nama' => 'Dr. Sari Wulandari']);
        $kunjung = Dosen::factory()->create(['nama' => 'Rina Amelia, M.T.']);

        $pendataan = PendataanMagang::factory()->create([
            'mahasiswa_id' => $mahasiswa->id,
            'dosen_pembimbing_id' => $pembimbing->id,
            'dosen_kunjung_id' => null,
            'status_pendataan' => StatusPendataan::MenungguVerifikasi,
        ]);

        $staff = User::factory()->staffKaprodi()->create();
        $staffSession = $this->createSsoSession($staff);

        // 1. Staff views KPI dashboard & rekap table.
        $this->actingAs($staff)
            ->withSession($staffSession)
            ->getJson(route('staff.verifikasi.index'))
            ->assertOk()
            ->assertJsonPath('metrics.menunggu_verifikasi', 1)
            ->assertJsonPath('metrics.pendataan_aktif', 0);

        // 2. Requesting revision without a note is rejected; with note succeeds.
        $this->actingAs($staff)
            ->withSession($staffSession)
            ->patchJson(route('staff.verifikasi.status', $pendataan), [
                'status' => StatusPendataan::PerluRevisi->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['catatan_verifikasi']);

        $this->actingAs($staff)
            ->withSession($staffSession)
            ->patchJson(route('staff.verifikasi.status', $pendataan), [
                'status' => StatusPendataan::PerluRevisi->value,
                'catatan_verifikasi' => 'Perbaiki tanggal selesai sesuai LOA.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status_pendataan', StatusPendataan::PerluRevisi->value);

        // 3. Staff cannot generate SK before status is Terverifikasi (Policy denies 403).
        $this->actingAs($staff)
            ->withSession($staffSession)
            ->postJson(route('staff.verifikasi.generate-sk', $pendataan), [
                'jenis_sk' => JenisSk::SkMagang->value,
            ])
            ->assertForbidden();

        // 4. Staff verifies pendataan & assigns Pembimbing Lapangan (Dosen Kunjung).
        $this->actingAs($staff)
            ->withSession($staffSession)
            ->patchJson(route('staff.verifikasi.status', $pendataan), [
                'status' => StatusPendataan::Terverifikasi->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.status_pendataan', StatusPendataan::Terverifikasi->value);

        $this->actingAs($staff)
            ->withSession($staffSession)
            ->patchJson(route('staff.verifikasi.pembimbing-lapangan', $pendataan), [
                'dosen_kunjung_id' => $kunjung->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.dosen_kunjung_id', $kunjung->id);

        // 5. Staff generates Draf SK Magang & Draf SK Kunjung PDFs.
        $skResponse = $this->actingAs($staff)
            ->withSession($staffSession)
            ->postJson(route('staff.verifikasi.generate-sk', $pendataan), [
                'jenis_sk' => JenisSk::SkMagang->value,
            ]);

        $skResponse->assertCreated()
            ->assertJsonPath('data.status_pengesahan', StatusPengesahanSk::Draft->value);

        $skMagang = DokumenSk::query()->findOrFail($skResponse->json('data.id'));
        $this->assertStringStartsWith('SK/RPL/MG/'.now()->year.'/', $skMagang->nomor_sk);
        $this->assertNotEmpty($skMagang->checksum_sha256);
        Storage::disk('local')->assertExists($skMagang->file_path);
        $this->assertStringStartsWith('%PDF-', (string) Storage::disk('local')->get($skMagang->file_path));

        // 6. Bulk Otomatisasi Generasi SK creates missing SK Kunjung and skips existing SK Magang.
        $this->actingAs($staff)
            ->withSession($staffSession)
            ->postJson(route('staff.verifikasi.generate-sk-bulk'))
            ->assertOk()
            ->assertJsonPath('summary.generated', 1)
            ->assertJsonPath('summary.skipped', 1);

        $this->assertSame(2, DokumenSk::query()->where('pendataan_magang_id', $pendataan->id)->count());

        // 7. Mahasiswa CANNOT download draft SK prior to Kaprodi approval.
        $mhsSession = $this->createSsoSession($mhsUser);
        $this->actingAs($mhsUser)
            ->withSession($mhsSession)
            ->get(route('files.sk', $skMagang))
            ->assertForbidden();

        // 8. Staff_Kaprodi & Admin CANNOT approve SK (exclusive to Kaprodi).
        $this->actingAs($staff)
            ->withSession($staffSession)
            ->postJson(route('kaprodi.sk.approve', $skMagang))
            ->assertForbidden();

        $admin = User::factory()->admin()->create();
        $adminSession = $this->createSsoSession($admin);
        $this->actingAs($admin)
            ->withSession($adminSession)
            ->postJson(route('kaprodi.sk.approve', $skMagang))
            ->assertForbidden();

        // Admin also cannot verify pendataan (strict separation from Staff_Kaprodi).
        $this->actingAs($admin)
            ->withSession($adminSession)
            ->patchJson(route('staff.verifikasi.status', $pendataan), [
                'status' => StatusPendataan::Terverifikasi->value,
            ])
            ->assertForbidden();

        // 9. Kaprodi approves SK (protected by live sso.fresh /api/sso/userinfo verification).
        $kaprodi = User::factory()->kaprodi()->create([
            'sso_sub' => 'sub-kaprodi-approve',
            'name' => 'Mochamad Iqbal Ardimansyah, S.T., M.Kom.',
            'email' => 'iqbal.kaprodi@upi.edu',
            'identifier_number' => '196008011986032001',
        ]);
        $kaprodiSession = $this->createSsoSession($kaprodi);

        Http::fake([
            'https://sso.test/api/sso/userinfo' => Http::response([
                'sub' => $kaprodi->sso_sub,
                'name' => $kaprodi->name,
                'email' => $kaprodi->email,
                'nip' => $kaprodi->identifier_number,
                'role' => 'Kaprodi',
            ], 200),
        ]);

        $oldChecksum = $skMagang->checksum_sha256;

        $this->actingAs($kaprodi)
            ->withSession($kaprodiSession)
            ->postJson(route('kaprodi.sk.approve', $skMagang))
            ->assertOk()
            ->assertJsonPath('data.status_pengesahan', StatusPengesahanSk::Disahkan->value)
            ->assertJsonPath('data.approved_by', $kaprodi->id);

        $skMagang->refresh();
        $this->assertNotSame($oldChecksum, $skMagang->checksum_sha256);
        Storage::disk('local')->assertExists($skMagang->file_path);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $kaprodi->id,
            'action' => 'sk.approved',
            'subject_id' => $skMagang->id,
        ]);

        // 10. Approved SK cannot be overwritten by re-generating draft.
        $this->actingAs($staff)
            ->withSession($staffSession)
            ->postJson(route('staff.verifikasi.generate-sk', $pendataan), [
                'jenis_sk' => JenisSk::SkMagang->value,
            ])
            ->assertUnprocessable();

        // 11. Now Mahasiswa CAN download their own approved SK, while another student still gets 403!
        $this->actingAs($mhsUser)
            ->withSession($mhsSession)
            ->get(route('files.sk', $skMagang))
            ->assertOk();

        $otherMhs = User::factory()->mahasiswa()->create();
        Mahasiswa::factory()->create(['user_id' => $otherMhs->id]);
        $this->actingAs($otherMhs)
            ->withSession($this->createSsoSession($otherMhs))
            ->get(route('files.sk', $skMagang))
            ->assertForbidden();
    }

    /**
     * @return array<string, string>
     */
    private function createSsoSession(User $user): array
    {
        $ssoSession = SsoSession::factory()->create([
            'user_id' => $user->id,
            'access_token_encrypted' => Crypt::encryptString('live-token-for-'.$user->id),
        ]);

        return [SsoSessionManager::SESSION_TOKEN_KEY => $ssoSession->session_id];
    }
}
