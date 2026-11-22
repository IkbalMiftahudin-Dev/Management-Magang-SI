<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\SsoSession;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\InteractsWithFakeSso;
use Tests\TestCase;

class SsoAuthenticationTest extends TestCase
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
     * TC-SSO-01: Guest accessing root "/" is redirected to /sso/login and then SACIBIRU /api/sso/authorize with PKCE S256.
     */
    public function test_guest_accessing_root_is_redirected_to_sso_authorize_with_pkce(): void
    {
        $rootResponse = $this->get('/');
        $rootResponse->assertRedirect(route('login'));

        $loginResponse = $this->get(route('login'));
        $loginResponse->assertRedirect();

        $location = (string) $loginResponse->headers->get('Location');
        $this->assertStringStartsWith('https://sso.test/api/sso/authorize?', $location);

        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertSame('modul-magang', $query['client_id'] ?? null);
        $this->assertSame('http://localhost/auth/callback', $query['redirect_uri'] ?? null);
        $this->assertSame('code', $query['response_type'] ?? null);
        $this->assertSame('S256', $query['code_challenge_method'] ?? null);
        $this->assertNotEmpty($query['state'] ?? null);
        $this->assertSame(43, strlen((string) ($query['code_challenge'] ?? '')));

        $this->assertSame($query['state'], session('sso_state'));
        $this->assertNotEmpty(session('sso_verifier'));
        $this->assertNull(session('sso_return_to'));
    }

    /**
     * TC-SSO-02: SP-Initiated deep link preserves return_to and lands directly on the intended page after SSO callback.
     */
    public function test_sp_initiated_deep_link_returns_user_to_intended_path(): void
    {
        $keys = $this->generateRsaKeyPair('kid-sp');
        $accessToken = $this->signSsoAccessToken($keys['private_pem'], $keys['kid'], [
            'sub' => 'sub-mhs-sp',
            'name' => 'Mohamad Firdiansyah',
            'email' => 'firdiansyah@upi.edu',
            'nip' => '2402041',
            'role' => 'Mahasiswa',
        ]);

        Http::fake([
            'https://sso.test/api/sso/token' => Http::response([
                'access_token' => $accessToken,
                'token_type' => 'Bearer',
                'expires_in' => 900,
                'refresh_token' => 'refresh-initial-token',
            ], 200),
            'https://sso.test/api/sso/jwks' => Http::response([
                'keys' => [$keys['jwk']],
            ], 200),
        ]);

        $this->get('/mahasiswa/beranda?kategori=mitra')->assertRedirect(route('login'));

        $loginResponse = $this->get(route('login'));
        $state = (string) session('sso_state');
        $this->assertSame('/mahasiswa/beranda?kategori=mitra', session('sso_return_to'));

        $callbackResponse = $this->get('/auth/callback?code=valid-code-123&state='.$state);
        $callbackResponse->assertRedirect('/mahasiswa/beranda?kategori=mitra');

        $this->assertAuthenticated();
        $user = User::query()->where('sso_sub', 'sub-mhs-sp')->firstOrFail();
        $this->assertSame(UserRole::Mahasiswa, $user->role);
        $this->assertSame('2402041', $user->identifier_number);

        $this->assertDatabaseHas('sso_sessions', [
            'user_id' => $user->id,
        ]);

        Http::assertSent(static function (HttpClientRequest $request): bool {
            return $request->url() === 'https://sso.test/api/sso/token'
                && $request['grant_type'] === 'authorization_code'
                && $request['client_id'] === 'modul-magang'
                && $request['client_secret'] === 'super-secret-client-value'
                && $request['code'] === 'valid-code-123'
                && is_string($request['code_verifier'])
                && strlen($request['code_verifier']) === 64;
        });
    }

    /**
     * TC-SSO-03: IdP-Initiated login without return_to routes each of the 5 roles to its own landing dashboard.
     */
    #[DataProvider('roleDashboardProvider')]
    public function test_idp_initiated_login_dispatches_each_role_to_expected_dashboard(
        string $ssoRole,
        string $expectedRouteName,
    ): void {
        $keys = $this->generateRsaKeyPair('kid-role-'.$ssoRole);
        $accessToken = $this->signSsoAccessToken($keys['private_pem'], $keys['kid'], [
            'sub' => 'sub-'.$ssoRole,
            'email' => strtolower($ssoRole).'@upi.edu',
            'role' => $ssoRole,
        ]);

        Http::fake([
            'https://sso.test/api/sso/token' => Http::response([
                'access_token' => $accessToken,
                'token_type' => 'Bearer',
                'expires_in' => 900,
                'refresh_token' => 'refresh-'.$ssoRole,
            ], 200),
            'https://sso.test/api/sso/jwks' => Http::response([
                'keys' => [$keys['jwk']],
            ], 200),
        ]);

        $this->get(route('login'))->assertRedirect();
        $state = (string) session('sso_state');

        $this->get('/auth/callback?code=code-'.$ssoRole.'&state='.$state)
            ->assertRedirect(route($expectedRouteName));

        $this->assertAuthenticated();
        $this->assertSame($ssoRole, auth()->user()->role->value);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function roleDashboardProvider(): array
    {
        return [
            'Mahasiswa' => ['Mahasiswa', 'mahasiswa.beranda'],
            'Dosen' => ['Dosen', 'dosen.penilaian.index'],
            'Staff_Kaprodi' => ['Staff_Kaprodi', 'staff.verifikasi.index'],
            'Kaprodi' => ['Kaprodi', 'kaprodi.verifikasi.index'],
            'Admin' => ['Admin', 'admin.dashboard'],
        ];
    }

    /**
     * TC-SSO-04: Malicious or looping return_to values are rejected and fall back safely to the role dashboard.
     */
    #[DataProvider('unsafeReturnPathProvider')]
    public function test_unsafe_return_to_values_are_sanitized_to_role_dashboard(string $unsafeReturnTo): void
    {
        $keys = $this->generateRsaKeyPair('kid-open-redirect');
        $accessToken = $this->signSsoAccessToken($keys['private_pem'], $keys['kid'], [
            'sub' => 'sub-safe-check',
            'role' => 'Mahasiswa',
        ]);

        Http::fake([
            'https://sso.test/api/sso/token' => Http::response([
                'access_token' => $accessToken,
                'token_type' => 'Bearer',
                'expires_in' => 900,
                'refresh_token' => 'refresh-safe',
            ], 200),
            'https://sso.test/api/sso/jwks' => Http::response([
                'keys' => [$keys['jwk']],
            ], 200),
        ]);

        $this->get(route('login', ['return_to' => $unsafeReturnTo]))->assertRedirect();
        $this->assertNull(session('sso_return_to'));

        $state = (string) session('sso_state');

        $this->get('/auth/callback?code=code-safe&state='.$state)
            ->assertRedirect(route('mahasiswa.beranda'));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function unsafeReturnPathProvider(): array
    {
        return [
            'external https' => ['https://evil.example/phish'],
            'protocol relative' => ['//evil.example/phish'],
            'backslash trick' => ['/\\evil.example'],
            'logout loop' => ['/logout'],
            'login loop' => ['/sso/login'],
            'callback loop' => ['/auth/callback'],
            'logged out loop' => ['/logged-out'],
            'root loop' => ['/'],
        ];
    }

    /**
     * TC-SSO-05: Callback with mismatched state returns HTTP 400 and logs warning.
     */
    public function test_callback_rejects_mismatched_state(): void
    {
        $this->get(route('login'))->assertRedirect();

        $response = $this->get('/auth/callback?code=any-code&state=wrong-state');
        $response->assertStatus(400);
        $this->assertGuest();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'sso.state_mismatch',
        ]);
    }

    /**
     * TC-SSO-06: Callback with access_denied shows friendly 403 error page and logs event.
     */
    public function test_callback_handles_access_denied_gracefully(): void
    {
        $this->get(route('login'))->assertRedirect();
        $state = (string) session('sso_state');

        $response = $this->get('/auth/callback?error=access_denied&error_description=Role+tidak+diizinkan&state='.$state);
        $response->assertStatus(403);
        $response->assertSee('Akses Modul Ditolak');
        $this->assertGuest();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'sso.callback_error',
        ]);
    }

    /**
     * TC-SSO-07a: JWT verification rejects invalid iss, wrong aud, expired exp, and HS256 downgrade.
     */
    public function test_jwt_verification_rejects_invalid_issuer_audience_expiry_and_hs256(): void
    {
        $keys = $this->generateRsaKeyPair('kid-validation');

        $cases = [
            'wrong_issuer' => $this->signSsoAccessToken($keys['private_pem'], $keys['kid'], ['iss' => 'https://other-sso.test']),
            'wrong_audience' => $this->signSsoAccessToken($keys['private_pem'], $keys['kid'], ['aud' => 'modul-sidang']),
            'expired_token' => $this->signSsoAccessToken($keys['private_pem'], $keys['kid'], ['exp' => time() - 30]),
            'hs256_downgrade' => JWT::encode([
                'iss' => 'https://sso.test',
                'aud' => 'modul-magang',
                'sub' => 'sub-hs256',
                'role' => 'Mahasiswa',
                'exp' => time() + 600,
            ], 'shared-secret-attack-padding-1234567890', 'HS256'),
        ];

        foreach ($cases as $label => $badJwt) {
            Cache::flush();
            Http::fake([
                'https://sso.test/api/sso/token' => Http::response([
                    'access_token' => $badJwt,
                    'token_type' => 'Bearer',
                    'expires_in' => 900,
                    'refresh_token' => 'refresh-'.$label,
                ], 200),
                'https://sso.test/api/sso/jwks' => Http::response([
                    'keys' => [$keys['jwk']],
                ], 200),
            ]);

            $this->get(route('login'))->assertRedirect();
            $state = (string) session('sso_state');

            $this->get('/auth/callback?code=code-'.$label.'&state='.$state)
                ->assertStatus(401);

            $this->assertGuest();
        }
    }

    /**
     * TC-SSO-07b: When JWT arrives with a newly rotated kid not yet in JWKS cache, Modul 5 refetches JWKS once automatically.
     */
    public function test_jwt_verification_refetches_jwks_when_encountering_new_kid(): void
    {
        $oldKeys = $this->generateRsaKeyPair('kid-old');
        $newKeys = $this->generateRsaKeyPair('kid-rotated-2');

        Cache::put('sso:jwks', ['keys' => [$oldKeys['jwk']]], 300);

        $accessToken = $this->signSsoAccessToken($newKeys['private_pem'], $newKeys['kid'], [
            'sub' => 'sub-rotated',
            'role' => 'Dosen',
        ]);

        Http::fake([
            'https://sso.test/api/sso/token' => Http::response([
                'access_token' => $accessToken,
                'token_type' => 'Bearer',
                'expires_in' => 900,
                'refresh_token' => 'refresh-rotated',
            ], 200),
            'https://sso.test/api/sso/jwks' => Http::response([
                'keys' => [$newKeys['jwk']],
            ], 200),
        ]);

        $this->get(route('login'))->assertRedirect();
        $state = (string) session('sso_state');

        $this->get('/auth/callback?code=code-rotated&state='.$state)
            ->assertRedirect(route('dosen.penilaian.index'));

        $this->assertAuthenticated();
        Http::assertSentCount(2);
    }

    /**
     * TC-SSO-09: Single Logout clears sso_sessions, invalidates Laravel session, redirects to SSO /logout, and /logged-out renders without looping.
     */
    public function test_single_logout_clears_local_session_and_redirects_to_sso_logout(): void
    {
        $keys = $this->generateRsaKeyPair('kid-logout');
        $accessToken = $this->signSsoAccessToken($keys['private_pem'], $keys['kid'], [
            'sub' => 'sub-logout-user',
            'role' => 'Mahasiswa',
        ]);

        Http::fake([
            'https://sso.test/api/sso/token' => Http::response([
                'access_token' => $accessToken,
                'token_type' => 'Bearer',
                'expires_in' => 900,
                'refresh_token' => 'refresh-logout',
            ], 200),
            'https://sso.test/api/sso/jwks' => Http::response([
                'keys' => [$keys['jwk']],
            ], 200),
        ]);

        $this->get(route('login'))->assertRedirect();
        $state = (string) session('sso_state');
        $this->get('/auth/callback?code=code-logout&state='.$state)->assertRedirect(route('mahasiswa.beranda'));

        $this->assertSame(1, SsoSession::query()->count());

        $logoutResponse = $this->post(route('logout'));
        $logoutResponse->assertRedirect(
            'https://sso.test/api/sso/logout?'.http_build_query([
                'client_id' => 'modul-magang',
                'post_logout_redirect_uri' => 'http://localhost/logged-out',
            ])
        );

        $this->assertGuest();
        $this->assertSame(0, SsoSession::query()->count());
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'sso.logout',
        ]);

        // Visiting /logged-out as guest must render cleanly with 200 (no redirect loop to /sso/login).
        $this->get(route('sso.logged-out'))
            ->assertOk()
            ->assertSee('Anda telah keluar');

        // Ensure secrets/tokens were never persisted into activity_logs properties.
        $allLogs = ActivityLog::query()->get()->toJson();
        $this->assertStringNotContainsString('refresh-logout', $allLogs);
        $this->assertStringNotContainsString('super-secret-client-value', $allLogs);
    }
}
