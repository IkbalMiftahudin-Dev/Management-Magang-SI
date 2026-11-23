<?php

namespace Tests\Feature;

use App\Models\BerkasAkhir;
use App\Models\Dosen;
use App\Models\KonversiMataKuliah;
use App\Models\LowonganMagang;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\PendataanMagang;
use App\Models\SsoSession;
use App\Models\User;
use App\Services\SsoSessionManager;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiRenderFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_ui_magang_1_0_pages_render_cleanly_across_all_5_roles(): void
    {
        $this->seed(MasterDataSeeder::class);

        $lowongan = LowonganMagang::query()->firstOrFail();
        $cyberSecurity = MataKuliah::query()->where('kode_mk', 'IF352')->firstOrFail();

        // 1. Mahasiswa views (Beranda, Detail Lowongan, Pendataan 4-Step, Logbook, Berkas Akhir, Hasil SK & Nilai)
        $mhsUser = User::factory()->mahasiswa()->create(['name' => 'Mohamad Firdiansyah']);
        $mahasiswa = Mahasiswa::factory()->create(['user_id' => $mhsUser->id, 'nim' => '2402041', 'nama' => 'Mohamad Firdiansyah']);
        $mhsSession = $this->createSsoSession($mhsUser);

        $pendataan = PendataanMagang::factory()->verified()->create([
            'mahasiswa_id' => $mahasiswa->id,
        ]);
        BerkasAkhir::factory()->mandatoryComplete()->create(['pendataan_magang_id' => $pendataan->id]);

        $this->actingAs($mhsUser)->withSession($mhsSession)->get(route('mahasiswa.beranda'))
            ->assertOk()->assertSee('Mulai langkah menuju')->assertSee('Terintegrasi SSO');

        $this->actingAs($mhsUser)->withSession($mhsSession)->get(route('mahasiswa.lowongan.show', $lowongan))
            ->assertOk()->assertSee($lowongan->judul_lowongan);

        $this->actingAs($mhsUser)->withSession($mhsSession)->get(route('mahasiswa.pendataan.show'))
            ->assertOk()->assertSee('Ringkasan Pendataan');

        $this->actingAs($mhsUser)->withSession($mhsSession)->get(route('mahasiswa.logbook.index'))
            ->assertOk()->assertSee('Catat Kegiatan')->assertSee('Riwayat Kegiatan');

        $this->actingAs($mhsUser)->withSession($mhsSession)->get(route('mahasiswa.berkas-akhir.show'))
            ->assertOk()->assertSee('One-Stop Submission')->assertSee('Opsional');

        $this->actingAs($mhsUser)->withSession($mhsSession)->get(route('mahasiswa.hasil.index'))
            ->assertOk()->assertSee('Dokumen SK Sah');

        // 2. Dosen views (Penilaian & SK list + Detail Penilaian & SK)
        $dosenUser = User::factory()->dosen()->create(['name' => 'Raditya Muhammad, S.T., M.T.']);
        $dosen = Dosen::query()->where('nip', '198211172006042001')->firstOrFail();
        $dosen->update(['user_id' => $dosenUser->id]);
        $dosenSession = $this->createSsoSession($dosenUser);

        $konversi = KonversiMataKuliah::factory()->create([
            'pendataan_magang_id' => $pendataan->id,
            'mata_kuliah_id' => $cyberSecurity->id,
            'dosen_penilai_id' => $dosen->id,
        ]);

        $this->actingAs($dosenUser)->withSession($dosenSession)->get(route('dosen.penilaian.index'))
            ->assertOk()->assertSee('Cyber Security')->assertSee('Mahasiswa Pengaju Konversi');

        $this->actingAs($dosenUser)->withSession($dosenSession)->get(route('dosen.penilaian.show', $konversi))
            ->assertOk()->assertSee('Rubrik Penilaian')->assertSee('Hasil Konversi');

        // 3. Staff_Kaprodi views (Kelola Lowongan, Verifikasi & Penilaian, Detail Verifikasi)
        $staffUser = User::factory()->staffKaprodi()->create();
        $staffSession = $this->createSsoSession($staffUser);

        $this->actingAs($staffUser)->withSession($staffSession)->get(route('staff.lowongan.index'))
            ->assertOk()->assertSee('Kelola Lowongan')->assertSee('Tambah Lowongan Baru');

        $this->actingAs($staffUser)->withSession($staffSession)->get(route('staff.verifikasi.index'))
            ->assertOk()->assertSee('Otomatisasi Generasi SK')->assertSee('Rekap Pendataan Mahasiswa');

        $this->actingAs($staffUser)->withSession($staffSession)->get(route('staff.verifikasi.show', $pendataan))
            ->assertOk()->assertSee('Keputusan Verifikasi Pendataan');

        // 4. Kaprodi views (Kelola Lowongan, Verifikasi & Penilaian, Detail Verifikasi)
        $kaprodiUser = User::factory()->kaprodi()->create();
        $kaprodiSession = $this->createSsoSession($kaprodiUser);

        $this->actingAs($kaprodiUser)->withSession($kaprodiSession)->get(route('kaprodi.lowongan.index'))
            ->assertOk()->assertSee('Kelola Lowongan');

        $this->actingAs($kaprodiUser)->withSession($kaprodiSession)->get(route('kaprodi.verifikasi.index'))
            ->assertOk()->assertSee('Otomatisasi Generasi SK');

        $this->actingAs($kaprodiUser)->withSession($kaprodiSession)->get(route('kaprodi.verifikasi.show', $pendataan))
            ->assertOk()->assertSee('Dokumen SK Magang');

        // 5. Admin views (Dashboard, Kelola Lowongan, Mata Kuliah & Rubrik, Audit Trail)
        $adminUser = User::factory()->admin()->create();
        $adminSession = $this->createSsoSession($adminUser);

        $this->actingAs($adminUser)->withSession($adminSession)->get(route('admin.dashboard'))
            ->assertOk()->assertSee('Dashboard Tata Kelola Sistem');

        $this->actingAs($adminUser)->withSession($adminSession)->get(route('admin.lowongan.index'))
            ->assertOk()->assertSee('Kelola Lowongan');

        $this->actingAs($adminUser)->withSession($adminSession)->get(route('admin.mata-kuliah.index'))
            ->assertOk()->assertSee('Master Mata Kuliah');

        $this->actingAs($adminUser)->withSession($adminSession)->get(route('admin.audit-logs.index'))
            ->assertOk()->assertSee('Audit Trail Sistem');
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
