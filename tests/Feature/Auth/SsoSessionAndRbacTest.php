<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\SsoSession;
use App\Models\User;
use App\Services\SsoSessionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\Support\InteractsWithFakeSso;
use Tests\TestCase;

class SsoSessionAndRbacTest extends TestCase
{
    use InteractsWithFakeSso;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureTestSso();
        Cache::flush();
    }

    /**
     * TC-SSO-08a: Expired access token triggers automatic rotation, saves new refresh token, and syncs updated user role.
     */
    public function test_middleware_auto_refreshes_expired_token_rotates_refresh_token_and_syncs_role(): void
    {
        $keys = $this->generateRsaKeyPair('kid-refresh');
        $user = User::factory()->staffKaprodi()->create([
            'sso_sub' => 'sub-refresh-1',
            'name' => 'Staff Lama',
            'email' => 'staff@upi.edu',
        ]);

        $newAccessToken = $this->signSsoAccessToken($keys['private_pem'], $keys['kid'], [
            'sub' => 'sub-refresh-1',
            'name' => 'Staff Terbarui',
            'email' => 'staff@upi.edu',
            'nip' => '199101012020011001',
            'role' => 'Staff_Kaprodi',
            'exp' => time() + 900,
        ]);

        Http::fake([
            'https://sso.test/api/sso/token' => Http::response([
                'access_token' => $newAccessToken,
                'token_type' => 'Bearer',
                'expires_in' => 900,
                'refresh_token' => 'rotated-refresh-token-v2',
            ], 200),
            'https://sso.test/api/sso/jwks' => Http::response([
                'keys' => [$keys['jwk']],
            ], 200),
        ]);

        $ssoSession = SsoSession::factory()->expired()->create([
            'user_id' => $user->id,
            'refresh_token_encrypted' => Crypt::encryptString('old-refresh-token-v1'),
        ]);

        $response = $this->actingAs($user)
            ->withSession([SsoSessionManager::SESSION_TOKEN_KEY => $ssoSession->session_id])
            ->get(route('staff.verifikasi.index'));
        $response->assertOk();
        $response->assertSee('Staff Terbarui');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');

        $ssoSession->refresh();
        $this->assertSame('rotated-refresh-token-v2', $ssoSession->decryptedRefreshToken());
        $this->assertTrue($ssoSession->access_token_expires_at->isFuture());

        $user->refresh();
        $this->assertSame('Staff Terbarui', $user->name);
    }

    /**
     * TC-SSO-08b: When SSO responds invalid_grant during refresh, local session is revoked and user is redirected to /sso/login.
     */
    public function test_middleware_forces_logout_when_refresh_token_returns_invalid_grant(): void
    {
        $user = User::factory()->mahasiswa()->create();

        Http::fake([
            'https://sso.test/api/sso/token' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'Refresh token dicabut.',
            ], 400),
        ]);

        $ssoSession = SsoSession::factory()->expired()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession([SsoSessionManager::SESSION_TOKEN_KEY => $ssoSession->session_id])
            ->get(route('mahasiswa.beranda'));
        $response->assertRedirect(route('login', ['return_to' => '/mahasiswa/beranda']));

        $this->assertGuest();
        $this->assertDatabaseMissing('sso_sessions', ['id' => $ssoSession->id]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'sso.session_revoked',
        ]);
    }

    /**
     * TC-SSO-08c: Critical routes guarded with sso.fresh call /api/sso/userinfo and revoke session on 401.
     */
    public function test_sso_fresh_middleware_verifies_userinfo_and_blocks_revoked_tokens(): void
    {
        Route::middleware(['web', 'auth', 'sso.session', 'sso.fresh'])
            ->get('/_test/critical-action', static fn () => response('ok', 200));

        $kaprodi = User::factory()->kaprodi()->create(['sso_sub' => 'sub-kaprodi-fresh']);

        $ssoSession = SsoSession::factory()->create([
            'user_id' => $kaprodi->id,
            'access_token_encrypted' => Crypt::encryptString('live-access-token'),
        ]);

        Http::fake([
            'https://sso.test/api/sso/userinfo' => Http::response([
                'error' => 'invalid_token',
            ], 401),
        ]);

        $this->actingAs($kaprodi)
            ->withSession([SsoSessionManager::SESSION_TOKEN_KEY => $ssoSession->session_id])
            ->get('/_test/critical-action')
            ->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $kaprodi->id,
            'action' => 'sso.userinfo_failed',
        ]);
    }

    /**
     * TC-SSO-10a: Cross-role route access returns 403 Forbidden and writes rbac.forbidden audit log.
     */
    public function test_cross_role_route_access_is_blocked_with_403_and_audited(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();

        $ssoSession = SsoSession::factory()->create([
            'user_id' => $mahasiswa->id,
        ]);

        $session = [SsoSessionManager::SESSION_TOKEN_KEY => $ssoSession->session_id];

        $this->actingAs($mahasiswa)->withSession($session)->get(route('kaprodi.verifikasi.index'))->assertForbidden();
        $this->actingAs($mahasiswa)->withSession($session)->get(route('staff.verifikasi.index'))->assertForbidden();
        $this->actingAs($mahasiswa)->withSession($session)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($mahasiswa)->withSession($session)->get(route('dosen.penilaian.index'))->assertForbidden();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $mahasiswa->id,
            'action' => 'rbac.forbidden',
        ]);
    }

    /**
     * TC-SSO-10b: Staff_Kaprodi, Admin, Kaprodi, Dosen, and Mahasiswa hold strictly separated Gate permissions.
     */
    public function test_gate_matrix_strictly_separates_staff_kaprodi_admin_kaprodi_dosen_and_mahasiswa(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->make();
        $dosen = User::factory()->dosen()->make();
        $staff = User::factory()->staffKaprodi()->make();
        $kaprodi = User::factory()->kaprodi()->make();
        $admin = User::factory()->admin()->make();

        // Staff_Kaprodi handles daily prodi verification & draft SK, but cannot approve SK or manage master data/audit logs.
        $this->assertTrue(Gate::forUser($staff)->allows('verify-pendataan'));
        $this->assertTrue(Gate::forUser($staff)->allows('plot-pembimbing-lapangan'));
        $this->assertTrue(Gate::forUser($staff)->allows('generate-draft-sk'));
        $this->assertFalse(Gate::forUser($staff)->allows('approve-sk'));
        $this->assertFalse(Gate::forUser($staff)->allows('manage-master-data'));
        $this->assertFalse(Gate::forUser($staff)->allows('view-audit-logs'));

        // Admin handles system governance (master data, audit logs, lowongan), but cannot verify pendataan, generate SK, or approve SK.
        $this->assertTrue(Gate::forUser($admin)->allows('manage-master-data'));
        $this->assertTrue(Gate::forUser($admin)->allows('view-audit-logs'));
        $this->assertTrue(Gate::forUser($admin)->allows('manage-lowongan'));
        $this->assertFalse(Gate::forUser($admin)->allows('verify-pendataan'));
        $this->assertFalse(Gate::forUser($admin)->allows('generate-draft-sk'));
        $this->assertFalse(Gate::forUser($admin)->allows('approve-sk'));

        // Kaprodi exclusively approves final SK.
        $this->assertTrue(Gate::forUser($kaprodi)->allows('approve-sk'));
        $this->assertFalse(Gate::forUser($dosen)->allows('approve-sk'));
        $this->assertFalse(Gate::forUser($mahasiswa)->allows('approve-sk'));

        // Dosen exclusively grades conversions and validates logbooks.
        $this->assertTrue(Gate::forUser($dosen)->allows('grade-konversi'));
        $this->assertTrue(Gate::forUser($dosen)->allows('validate-logbook'));
        $this->assertFalse(Gate::forUser($kaprodi)->allows('grade-konversi'));
        $this->assertFalse(Gate::forUser($staff)->allows('grade-konversi'));

        // Mahasiswa exclusively submits pendataan, logbooks, and final documents.
        $this->assertTrue(Gate::forUser($mahasiswa)->allows('submit-pendataan'));
        $this->assertTrue(Gate::forUser($mahasiswa)->allows('fill-logbook'));
        $this->assertTrue(Gate::forUser($mahasiswa)->allows('submit-berkas-akhir'));
        $this->assertFalse(Gate::forUser($dosen)->allows('submit-pendataan'));
        $this->assertSame(UserRole::StaffKaprodi, $staff->role);
    }
}
