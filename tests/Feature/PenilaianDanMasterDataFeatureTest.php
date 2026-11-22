<?php

namespace Tests\Feature;

use App\Enums\StatusPendataan;
use App\Enums\StatusPenilaian;
use App\Models\BerkasAkhir;
use App\Models\Dosen;
use App\Models\KonversiMataKuliah;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\PendataanMagang;
use App\Models\SsoSession;
use App\Models\User;
use App\Services\SsoSessionManager;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\Support\InteractsWithFakeSso;
use Tests\TestCase;

class PenilaianDanMasterDataFeatureTest extends TestCase
{
    use InteractsWithFakeSso;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureTestSso();
    }

    /**
     * TC-M05: Assigned Dosen Pengampu MK saves draft & publishes weighted rubric grade (with live sso.fresh),
     * locks grade permanently, transitions pendataan to Selesai, and blocks unrelated lecturers.
     */
    public function test_dosen_pengampu_grades_weighted_rubric_publishes_with_sso_fresh_and_locks(): void
    {
        $this->seed(MasterDataSeeder::class);

        $dosenUser = User::factory()->dosen()->create([
            'sso_sub' => 'sub-raditya',
            'name' => 'Raditya Muhammad, S.T., M.T.',
            'email' => 'raditya@upi.edu',
            'identifier_number' => '198211172006042001',
        ]);
        $dosen = Dosen::query()->where('nip', '198211172006042001')->firstOrFail();
        $dosen->update(['user_id' => $dosenUser->id]);
        $dosenSession = $this->createSsoSession($dosenUser);

        $otherDosenUser = User::factory()->dosen()->create();
        Dosen::factory()->create(['user_id' => $otherDosenUser->id]);
        $otherDosenSession = $this->createSsoSession($otherDosenUser);

        $mhsUser = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create(['user_id' => $mhsUser->id, 'nim' => '2402041']);

        $cyberSecurity = MataKuliah::query()->where('kode_mk', 'IF352')->with('rubrikKomponens')->firstOrFail();

        $pendataan = PendataanMagang::factory()->verified()->create([
            'mahasiswa_id' => $mahasiswa->id,
        ]);

        BerkasAkhir::factory()->mandatoryComplete()->create([
            'pendataan_magang_id' => $pendataan->id,
        ]);

        $konversi = KonversiMataKuliah::factory()->create([
            'pendataan_magang_id' => $pendataan->id,
            'mata_kuliah_id' => $cyberSecurity->id,
            'dosen_penilai_id' => $dosen->id,
            'sks_konversi' => 3,
        ]);

        // 1. Lecturer dashboard shows course banner, 4 KPIs, and applicant list.
        $this->actingAs($dosenUser)
            ->withSession($dosenSession)
            ->getJson(route('dosen.penilaian.index'))
            ->assertOk()
            ->assertJsonPath('selected_course.kode_mk', 'IF352')
            ->assertJsonPath('metrics.pengajuan', 1)
            ->assertJsonPath('metrics.belum_dinilai', 1);

        $scoresPayload = [];
        $uiScores = [1 => 88, 2 => 90, 3 => 86, 4 => 89];

        foreach ($cyberSecurity->rubrikKomponens as $komponen) {
            $scoresPayload[] = [
                'rubrik_komponen_id' => $komponen->id,
                'nilai' => $uiScores[$komponen->urutan],
            ];
        }

        // 2. Unrelated lecturer is blocked (403) from grading this course.
        $this->actingAs($otherDosenUser)
            ->withSession($otherDosenSession)
            ->postJson(route('dosen.penilaian.draft', $konversi), [
                'nilai_instansi' => 91,
                'scores' => $scoresPayload,
            ])
            ->assertForbidden();

        // 3. Assigned lecturer saves draft first (editable, unlocked).
        $this->actingAs($dosenUser)
            ->withSession($dosenSession)
            ->postJson(route('dosen.penilaian.draft', $konversi), [
                'nilai_instansi' => 91,
                'catatan_dosen' => 'Draf awal penilaian.',
                'scores' => $scoresPayload,
            ])
            ->assertOk()
            ->assertJsonPath('data.status_penilaian', StatusPenilaian::Draf->value)
            ->assertJsonPath('data.is_locked', false)
            ->assertJsonPath('data.nilai_huruf', 'A');

        // 4. Lecturer publishes grade (protected by live sso.fresh /api/sso/userinfo check).
        Http::fake([
            'https://sso.test/api/sso/userinfo' => Http::response([
                'sub' => $dosenUser->sso_sub,
                'name' => $dosenUser->name,
                'email' => $dosenUser->email,
                'nip' => $dosenUser->identifier_number,
                'role' => 'Dosen',
            ], 200),
        ]);

        $this->actingAs($dosenUser)
            ->withSession($dosenSession)
            ->postJson(route('dosen.penilaian.publish', $konversi), [
                'nilai_instansi' => 91,
                'catatan_dosen' => 'Mahasiswa mampu menerapkan konsep monitoring keamanan dengan baik.',
                'scores' => $scoresPayload,
            ])
            ->assertOk()
            ->assertJsonPath('data.status_penilaian', StatusPenilaian::Dipublikasikan->value)
            ->assertJsonPath('data.is_locked', true)
            ->assertJsonPath('data.nilai_akhir', '88.35')
            ->assertJsonPath('data.nilai_huruf', 'A');

        // All converted courses published -> pendataan transitions to Selesai.
        $pendataan->refresh();
        $this->assertSame(StatusPendataan::Selesai, $pendataan->status_pendataan);

        // Once locked, further grade changes are rejected (403 via Policy).
        $this->actingAs($dosenUser)
            ->withSession($dosenSession)
            ->postJson(route('dosen.penilaian.draft', $konversi), [
                'nilai_instansi' => 50,
                'scores' => $scoresPayload,
            ])
            ->assertForbidden();

        // Mahasiswa sees the published grade on their hasil endpoint.
        $this->actingAs($mhsUser)
            ->withSession($this->createSsoSession($mhsUser))
            ->getJson(route('mahasiswa.hasil.index'))
            ->assertOk()
            ->assertJsonCount(1, 'published_grades');
    }

    /**
     * Admin Master Data & Audit Logs: Admin manages courses with strict 100% rubric weight validation and inspects audit logs; Staff_Kaprodi is blocked.
     */
    public function test_admin_manages_master_data_enforces_100_percent_rubric_and_reads_audit_logs(): void
    {
        $admin = User::factory()->admin()->create();
        $adminSession = $this->createSsoSession($admin);
        $dosen = Dosen::factory()->create();

        // Reject rubric whose total weight != 100%.
        $this->actingAs($admin)
            ->withSession($adminSession)
            ->postJson(route('admin.mata-kuliah.store'), [
                'kode_mk' => 'IF401',
                'nama_mk' => 'Cloud Computing',
                'sks' => 3,
                'dosen_pengampu_id' => $dosen->id,
                'rubrik_komponens' => [
                    ['nama_komponen' => 'Arsitektur Cloud', 'bobot_persen' => 40],
                    ['nama_komponen' => 'Deployment', 'bobot_persen' => 40],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rubrik_komponens']);

        // Accept valid rubric summing exactly to 100%.
        $this->actingAs($admin)
            ->withSession($adminSession)
            ->postJson(route('admin.mata-kuliah.store'), [
                'kode_mk' => 'IF401',
                'nama_mk' => 'Cloud Computing',
                'sks' => 3,
                'dosen_pengampu_id' => $dosen->id,
                'rubrik_komponens' => [
                    ['nama_komponen' => 'Arsitektur Cloud', 'bobot_persen' => 50],
                    ['nama_komponen' => 'Deployment & Monitoring', 'bobot_persen' => 50],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.kode_mk', 'IF401')
            ->assertJsonCount(2, 'data.rubrik_komponens');

        // Admin reads audit logs containing the mata_kuliah.created event.
        $this->actingAs($admin)
            ->withSession($adminSession)
            ->getJson(route('admin.audit-logs.index', ['action' => 'mata_kuliah.created']))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // Staff_Kaprodi is strictly blocked from Admin master data & audit logs.
        $staff = User::factory()->staffKaprodi()->create();
        $staffSession = $this->createSsoSession($staff);

        $this->actingAs($staff)->withSession($staffSession)->getJson(route('admin.mata-kuliah.index'))->assertForbidden();
        $this->actingAs($staff)->withSession($staffSession)->getJson(route('admin.audit-logs.index'))->assertForbidden();
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
