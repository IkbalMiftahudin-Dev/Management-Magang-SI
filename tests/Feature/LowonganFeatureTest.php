<?php

namespace Tests\Feature;

use App\Enums\StatusLowongan;
use App\Enums\SumberLowongan;
use App\Models\Dosen;
use App\Models\LowonganMagang;
use App\Models\SsoSession;
use App\Models\User;
use App\Services\SsoSessionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowonganFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-M01: Mahasiswa sees only active vacancies and can search & filter by Mitra Kampus / Tawaran Dosen.
     */
    public function test_mahasiswa_can_search_and_filter_only_active_vacancies(): void
    {
        $user = User::factory()->mahasiswa()->create();
        $session = $this->createSsoSession($user);

        $mitra = LowonganMagang::factory()->create([
            'judul_lowongan' => 'UI/UX Designer Intern',
            'nama_instansi' => 'Telkom Indonesia',
            'sumber_lowongan' => SumberLowongan::MitraKampus,
            'status' => StatusLowongan::Aktif,
        ]);

        LowonganMagang::factory()->create([
            'judul_lowongan' => 'Research Assistant',
            'nama_instansi' => 'Lab RPL Informatika',
            'sumber_lowongan' => SumberLowongan::TawaranDosen,
            'status' => StatusLowongan::Aktif,
        ]);

        $draft = LowonganMagang::factory()->create([
            'judul_lowongan' => 'Hidden Draft Vacancy',
            'status' => StatusLowongan::Draft,
        ]);

        $this->actingAs($user)
            ->withSession($session)
            ->getJson(route('mahasiswa.beranda'))
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->actingAs($user)
            ->withSession($session)
            ->getJson(route('mahasiswa.beranda', ['sumber' => 'mitra_kampus', 'search' => 'Telkom']))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $mitra->id);

        // Mahasiswa cannot view draft vacancy detail.
        $this->actingAs($user)
            ->withSession($session)
            ->getJson(route('mahasiswa.lowongan.show', $draft))
            ->assertForbidden();

        // Mahasiswa can view active vacancy detail.
        $this->actingAs($user)
            ->withSession($session)
            ->getJson(route('mahasiswa.lowongan.show', $mitra))
            ->assertOk()
            ->assertJsonPath('data.judul_lowongan', 'UI/UX Designer Intern');
    }

    /**
     * TC-M01 Management: Staff_Kaprodi, Kaprodi, and Admin can CRUD vacancies and get accurate KPI metrics; Mahasiswa/Dosen are blocked.
     */
    public function test_staff_kaprodi_and_admin_can_crud_vacancies_with_audit_trail(): void
    {
        $staff = User::factory()->staffKaprodi()->create();
        $staffSession = $this->createSsoSession($staff);
        $dosen = Dosen::factory()->create();

        $createResponse = $this->actingAs($staff)
            ->withSession($staffSession)
            ->postJson(route('staff.lowongan.store'), [
                'judul_lowongan' => 'Backend Laravel Intern',
                'nama_instansi' => 'PT SinerGo Digital',
                'posisi_bidang' => 'Backend Engineering',
                'lokasi_mode' => 'Bandung · Hybrid',
                'sumber_lowongan' => SumberLowongan::MitraKampus->value,
                'kontak' => 'hr@sinergo.id',
                'deskripsi' => 'Membangun REST API Laravel.',
                'persyaratan' => 'PHP 8.3 dan MySQL.',
                'status' => StatusLowongan::Aktif->value,
                'dosen_penanggung_jawab_id' => $dosen->id,
            ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.judul_lowongan', 'Backend Laravel Intern');

        $lowonganId = (int) $createResponse->json('data.id');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $staff->id,
            'action' => 'lowongan.created',
            'subject_id' => $lowonganId,
        ]);

        // Kaprodi can list with metrics & update status to archive.
        $kaprodi = User::factory()->kaprodi()->create();
        $kaprodiSession = $this->createSsoSession($kaprodi);

        $this->actingAs($kaprodi)
            ->withSession($kaprodiSession)
            ->getJson(route('kaprodi.lowongan.index'))
            ->assertOk()
            ->assertJsonPath('metrics.aktif', 1)
            ->assertJsonPath('metrics.mitra_terhubung', 1);

        $this->actingAs($kaprodi)
            ->withSession($kaprodiSession)
            ->putJson(route('kaprodi.lowongan.update', $lowonganId), [
                'status' => StatusLowongan::Arsip->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'arsip');

        // Admin can delete vacancy.
        $admin = User::factory()->admin()->create();
        $adminSession = $this->createSsoSession($admin);

        $this->actingAs($admin)
            ->withSession($adminSession)
            ->deleteJson(route('admin.lowongan.destroy', $lowonganId))
            ->assertOk()
            ->assertJsonPath('deleted', true);

        $this->assertDatabaseMissing('lowongan_magangs', ['id' => $lowonganId]);

        // Mahasiswa cannot hit management endpoints.
        $mhs = User::factory()->mahasiswa()->create();
        $this->actingAs($mhs)
            ->withSession($this->createSsoSession($mhs))
            ->postJson(route('staff.lowongan.store'), [])
            ->assertForbidden();
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
