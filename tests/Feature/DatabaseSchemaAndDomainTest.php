<?php

namespace Tests\Feature;

use App\Data\SsoUserClaims;
use App\Enums\JenisProgramMagang;
use App\Enums\JenisSk;
use App\Enums\ModeKegiatan;
use App\Enums\StatusBerkasAkhir;
use App\Enums\StatusPendataan;
use App\Enums\StatusPenilaian;
use App\Enums\UserRole;
use App\Models\BerkasAkhir;
use App\Models\DokumenSk;
use App\Models\Dosen;
use App\Models\KonversiMataKuliah;
use App\Models\Logbook;
use App\Models\LowonganMagang;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\PendataanMagang;
use App\Models\PenilaianKonversiDetail;
use App\Services\SsoUserSyncService;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSchemaAndDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_data_seeder_is_idempotent_and_seeds_100_percent_rubric_weights(): void
    {
        $this->seed(MasterDataSeeder::class);
        $this->seed(MasterDataSeeder::class);

        $this->assertSame(6, Dosen::query()->count());
        $this->assertSame(4, MataKuliah::query()->count());
        $this->assertSame(5, LowonganMagang::query()->count());

        $cyberSecurity = MataKuliah::query()->where('kode_mk', 'IF352')->firstOrFail();
        $this->assertSame(4, $cyberSecurity->rubrikKomponens()->count());
        $this->assertSame(100, $cyberSecurity->totalRubrikWeight());
        $this->assertSame('Raditya Muhammad, S.T., M.T.', $cyberSecurity->dosenPengampu->nama);
    }

    public function test_full_modul5_relational_graph_and_weighted_rubric_calculation(): void
    {
        $this->seed(MasterDataSeeder::class);

        $mahasiswa = Mahasiswa::factory()->create(['nim' => '2402041', 'nama' => 'Mohamad Firdiansyah']);
        $pembimbing = Dosen::query()->where('email', 'sari.wulandari@upi.edu')->firstOrFail();
        $kunjung = Dosen::query()->where('email', 'rina.amelia@upi.edu')->firstOrFail();
        $cyberSecurity = MataKuliah::query()->where('kode_mk', 'IF352')->with('rubrikKomponens')->firstOrFail();

        $pendataan = PendataanMagang::factory()->verified()->create([
            'mahasiswa_id' => $mahasiswa->id,
            'dosen_pembimbing_id' => $pembimbing->id,
            'dosen_kunjung_id' => $kunjung->id,
            'jenis_program' => JenisProgramMagang::Msib,
            'mode_kegiatan' => ModeKegiatan::Hybrid,
            'tanggal_mulai' => '2026-04-02',
            'tanggal_selesai' => '2026-09-17',
        ]);

        $this->assertSame(24, $pendataan->durationInWeeks());
        $this->assertSame(StatusPendataan::Terverifikasi, $pendataan->status_pendataan);

        $skMagang = DokumenSk::factory()->create([
            'pendataan_magang_id' => $pendataan->id,
            'jenis_sk' => JenisSk::SkMagang,
        ]);

        Logbook::factory()->count(3)->create([
            'pendataan_magang_id' => $pendataan->id,
        ]);

        $berkas = BerkasAkhir::factory()->mandatoryComplete()->create([
            'pendataan_magang_id' => $pendataan->id,
        ]);

        $konversi = KonversiMataKuliah::factory()->create([
            'pendataan_magang_id' => $pendataan->id,
            'mata_kuliah_id' => $cyberSecurity->id,
            'dosen_penilai_id' => $cyberSecurity->dosen_pengampu_id,
            'sks_konversi' => $cyberSecurity->sks,
            'nilai_instansi' => 91.00,
        ]);

        // Exact UI Magang 1.0 Cyber Security rubric scores: 88 (25%), 90 (35%), 86 (25%), 89 (15%) => 88.35 => Grade A
        $scores = [1 => 88.00, 2 => 90.00, 3 => 86.00, 4 => 89.00];

        foreach ($cyberSecurity->rubrikKomponens as $komponen) {
            PenilaianKonversiDetail::query()->create([
                'konversi_mata_kuliah_id' => $konversi->id,
                'rubrik_komponen_id' => $komponen->id,
                'bobot_persen_snapshot' => $komponen->bobot_persen,
                'nilai' => $scores[$komponen->urutan],
            ]);
        }

        $weighted = $konversi->calculateWeightedScore();
        $this->assertSame(88.35, $weighted);
        $this->assertSame('A', KonversiMataKuliah::scoreToGradeLetter($weighted));
        $this->assertSame('A-', KonversiMataKuliah::scoreToGradeLetter(82.5));
        $this->assertSame('B+', KonversiMataKuliah::scoreToGradeLetter(76.0));
        $this->assertSame('E', KonversiMataKuliah::scoreToGradeLetter(35.0));

        $this->assertSame(1, $pendataan->dokumenSks()->count());
        $this->assertSame($skMagang->id, $pendataan->dokumenSks->first()->id);
        $this->assertSame(3, $pendataan->logbooks()->count());
        $this->assertSame($berkas->id, $pendataan->berkasAkhir->id);
        $this->assertSame(3, $pendataan->totalConvertedSks());
        $this->assertSame(StatusPenilaian::BelumDinilai, $konversi->status_penilaian);
    }

    public function test_berkas_akhir_progress_respects_5_mandatory_and_1_optional_slot(): void
    {
        $partial = BerkasAkhir::factory()->create();
        $this->assertSame(3, $partial->mandatoryUploadedCount());
        $this->assertSame(3, $partial->totalUploadedCount());
        $this->assertSame(50, $partial->completionPercentage());
        $this->assertFalse($partial->isMandatoryComplete());

        $mandatoryDone = BerkasAkhir::factory()->mandatoryComplete()->create();
        $this->assertSame(5, $mandatoryDone->mandatoryUploadedCount());
        $this->assertSame(5, $mandatoryDone->totalUploadedCount());
        $this->assertSame(83, $mandatoryDone->completionPercentage());
        $this->assertTrue($mandatoryDone->isMandatoryComplete());
        $this->assertSame(StatusBerkasAkhir::SiapDinilai, $mandatoryDone->status_kelengkapan);

        $mandatoryDone->update([
            'sertifikat_path' => 'private/berkas/sertifikat.pdf',
            'sertifikat_uploaded_at' => now(),
        ]);

        $this->assertSame(6, $mandatoryDone->totalUploadedCount());
        $this->assertSame(100, $mandatoryDone->completionPercentage());
    }

    public function test_unique_constraints_prevent_duplicate_course_conversion_and_duplicate_sk_type(): void
    {
        $pendataan = PendataanMagang::factory()->verified()->create();
        $mataKuliah = MataKuliah::factory()->create();

        KonversiMataKuliah::factory()->create([
            'pendataan_magang_id' => $pendataan->id,
            'mata_kuliah_id' => $mataKuliah->id,
        ]);

        try {
            KonversiMataKuliah::factory()->create([
                'pendataan_magang_id' => $pendataan->id,
                'mata_kuliah_id' => $mataKuliah->id,
            ]);
            $this->fail('Expected unique constraint violation for duplicate konversi_mata_kuliahs.');
        } catch (QueryException) {
            $this->assertSame(1, KonversiMataKuliah::query()->where('pendataan_magang_id', $pendataan->id)->count());
        }

        DokumenSk::factory()->create([
            'pendataan_magang_id' => $pendataan->id,
            'jenis_sk' => JenisSk::SkMagang,
        ]);

        try {
            DokumenSk::factory()->create([
                'pendataan_magang_id' => $pendataan->id,
                'jenis_sk' => JenisSk::SkMagang,
            ]);
            $this->fail('Expected unique constraint violation for duplicate dokumen_sks jenis_sk.');
        } catch (QueryException) {
            $this->assertSame(1, DokumenSk::query()->where('pendataan_magang_id', $pendataan->id)->count());
        }
    }

    public function test_sso_user_sync_automatically_provisions_and_links_mahasiswa_and_dosen_profiles(): void
    {
        $this->seed(MasterDataSeeder::class);

        $syncService = app(SsoUserSyncService::class);
        $now = time();

        // 1. Existing seeded Dosen (by NIP) gets automatically linked on first SSO login without creating duplicates.
        $dosenUser = $syncService->syncFromClaims(new SsoUserClaims(
            sub: 'sso-sub-raditya',
            name: 'Raditya Muhammad, S.T., M.T.',
            email: 'raditya@upi.edu',
            nip: '198211172006042001',
            role: UserRole::Dosen,
            iss: 'https://sso.test',
            aud: 'modul-magang',
            iat: $now,
            exp: $now + 900,
        ));

        $this->assertNotNull($dosenUser->dosen);
        $this->assertSame('198211172006042001', $dosenUser->dosen->nip);
        $this->assertSame(6, Dosen::query()->count());

        // 2. New Mahasiswa login provisions a Mahasiswa academic profile automatically.
        $mhsUser = $syncService->syncFromClaims(new SsoUserClaims(
            sub: 'sso-sub-mhs-1',
            name: 'Ikbal Miftahudin',
            email: 'ikbal@upi.edu',
            nip: '2401426',
            role: UserRole::Mahasiswa,
            iss: 'https://sso.test',
            aud: 'modul-magang',
            iat: $now,
            exp: $now + 900,
        ));

        $this->assertNotNull($mhsUser->mahasiswa);
        $this->assertSame('2401426', $mhsUser->mahasiswa->nim);
        $this->assertSame('Rekayasa Perangkat Lunak', $mhsUser->mahasiswa->prodi);

        // 3. Staff_Kaprodi & Admin do NOT create fake academic profiles.
        $staffUser = $syncService->syncFromClaims(new SsoUserClaims(
            sub: 'sso-sub-staff-1',
            name: 'Staf Prodi RPL',
            email: 'staf@upi.edu',
            nip: '199501012022011001',
            role: UserRole::StaffKaprodi,
            iss: 'https://sso.test',
            aud: 'modul-magang',
            iat: $now,
            exp: $now + 900,
        ));

        $this->assertNull($staffUser->mahasiswa);
        $this->assertNull($staffUser->dosen);
    }
}
