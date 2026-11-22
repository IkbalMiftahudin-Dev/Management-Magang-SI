<?php

namespace App\Services;

use App\Data\SsoTokenPayload;
use App\Data\SsoUserClaims;
use App\Enums\UserRole;
use App\Exceptions\SsoException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class SsoService
{
    public const JWKS_CACHE_KEY = 'sso:jwks';

    /**
     * Paths that must never be used as post-login return_to targets to prevent redirect loops.
     *
     * @var list<string>
     */
    private const DISALLOWED_RETURN_PATHS = [
        '/',
        '/sso/login',
        '/auth/callback',
        '/logout',
        '/logged-out',
        '/auth/error',
    ];

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Base SSO URL without trailing slash.
     */
    public function baseUrl(): string
    {
        $url = rtrim((string) config('sso.url', ''), '/');

        if ($url === '') {
            throw new SsoException(
                errorCode: 'misconfigured',
                httpStatus: 500,
                publicMessage: 'Konfigurasi SSO_URL belum diisi.',
            );
        }

        return $url;
    }

    /**
     * Public client identifier registered in SACIBIRU SSO.
     */
    public function clientId(): string
    {
        $clientId = trim((string) config('sso.client_id', ''));

        if ($clientId === '') {
            throw new SsoException(
                errorCode: 'misconfigured',
                httpStatus: 500,
                publicMessage: 'Konfigurasi SSO_CLIENT_ID belum diisi.',
            );
        }

        return $clientId;
    }

    /**
     * Generate a cryptographically secure OAuth state value.
     */
    public function generateState(): string
    {
        return Str::random(40);
    }

    /**
     * Generate a PKCE S256 verifier and challenge pair.
     *
     * @return array{verifier: string, challenge: string}
     */
    public function generatePkcePair(): array
    {
        $verifier = Str::random(64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        return [
            'verifier' => $verifier,
            'challenge' => $challenge,
        ];
    }

    /**
     * Validate and sanitize an internal return_to path to prevent open redirects and login loops.
     */
    public function sanitizeReturnPath(?string $candidate): ?string
    {
        if ($candidate === null) {
            return null;
        }

        $candidate = trim($candidate);

        if ($candidate === '' || ! str_starts_with($candidate, '/')) {
            return null;
        }

        if (str_starts_with($candidate, '//') || str_contains($candidate, '\\')) {
            return null;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $candidate) === 1) {
            return null;
        }

        $parsed = parse_url($candidate);

        if ($parsed === false || isset($parsed['scheme']) || isset($parsed['host'])) {
            return null;
        }

        $path = $parsed['path'] ?? '/';

        if (in_array($path, self::DISALLOWED_RETURN_PATHS, true)) {
            return null;
        }

        if (str_starts_with($path, '/sso/') || str_starts_with($path, '/auth/')) {
            return null;
        }

        return $candidate;
    }

    /**
     * Build the full SACIBIRU /api/sso/authorize redirect URL.
     */
    public function buildAuthorizeUrl(string $state, string $codeChallenge): string
    {
        $redirectUri = trim((string) config('sso.redirect_uri', ''));

        if ($redirectUri === '') {
            throw new SsoException(
                errorCode: 'misconfigured',
                httpStatus: 500,
                publicMessage: 'Konfigurasi SSO_REDIRECT_URI belum diisi.',
            );
        }

        $query = http_build_query([
            'client_id' => $this->clientId(),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return $this->baseUrl().'/api/sso/authorize?'.$query;
    }

    /**
     * Build the SACIBIRU Single Logout URL.
     */
    public function buildLogoutUrl(?string $postLogoutRedirectUri = null): string
    {
        $params = [
            'client_id' => $this->clientId(),
        ];

        $targetUri = $postLogoutRedirectUri ?? config('sso.post_logout_redirect_uri');

        if (is_string($targetUri) && trim($targetUri) !== '') {
            $params['post_logout_redirect_uri'] = trim($targetUri);
        }

        return $this->baseUrl().'/api/sso/logout?'.http_build_query($params);
    }

    /**
     * Exchange a one-time OAuth authorization code for verified tokens.
     */
    public function exchangeAuthorizationCode(string $code, string $codeVerifier): SsoTokenPayload
    {
        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout($this->httpTimeout())
                ->post($this->baseUrl().'/api/sso/token', [
                    'grant_type' => 'authorization_code',
                    'client_id' => $this->clientId(),
                    'client_secret' => (string) config('sso.client_secret', ''),
                    'code' => $code,
                    'redirect_uri' => (string) config('sso.redirect_uri', ''),
                    'code_verifier' => $codeVerifier,
                ]);
        } catch (ConnectionException $exception) {
            throw new SsoException(
                errorCode: 'sso_unreachable',
                httpStatus: 503,
                publicMessage: 'Layanan SSO SACIBIRU sedang tidak dapat dihubungi.',
                previous: $exception,
            );
        }

        if (! $response->ok()) {
            $oauthError = (string) ($response->json('error') ?? 'token_exchange_failed');

            throw new SsoException(
                errorCode: $oauthError,
                httpStatus: 401,
                publicMessage: 'Pertukaran kode autentikasi SSO gagal. Silakan coba masuk kembali.',
                context: ['sso_http_status' => $response->status(), 'oauth_error' => $oauthError],
            );
        }

        return $this->parseTokenResponse($response->json());
    }

    /**
     * Rotate a refresh token to obtain a fresh access token and new refresh token.
     */
    public function refreshTokens(string $refreshToken): SsoTokenPayload
    {
        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout($this->httpTimeout())
                ->post($this->baseUrl().'/api/sso/token', [
                    'grant_type' => 'refresh_token',
                    'client_id' => $this->clientId(),
                    'client_secret' => (string) config('sso.client_secret', ''),
                    'refresh_token' => $refreshToken,
                ]);
        } catch (ConnectionException $exception) {
            throw new SsoException(
                errorCode: 'sso_unreachable',
                httpStatus: 503,
                publicMessage: 'Layanan SSO SACIBIRU sedang tidak dapat dihubungi.',
                previous: $exception,
            );
        }

        if (! $response->ok()) {
            $oauthError = (string) ($response->json('error') ?? 'invalid_grant');

            throw new SsoException(
                errorCode: $oauthError,
                httpStatus: 401,
                publicMessage: 'Sesi SSO Anda telah berakhir. Silakan masuk kembali.',
                context: ['sso_http_status' => $response->status(), 'oauth_error' => $oauthError],
            );
        }

        return $this->parseTokenResponse($response->json());
    }

    /**
     * Verify an RS256 access token locally using cached JWKS (with single automatic refetch on unknown kid).
     */
    public function verifyAccessToken(string $jwt): SsoUserClaims
    {
        $header = $this->decodeJwtHeader($jwt);

        if (($header['alg'] ?? null) !== 'RS256') {
            throw new SsoException(
                errorCode: 'invalid_jwt_algorithm',
                httpStatus: 401,
                publicMessage: 'Algoritma token SSO tidak diizinkan.',
                context: ['alg' => $header['alg'] ?? null],
            );
        }

        $kid = isset($header['kid']) && is_string($header['kid']) ? $header['kid'] : null;
        $keys = $this->resolveVerificationKeys($kid);

        try {
            $decoded = JWT::decode($jwt, $keys);
        } catch (ExpiredException $exception) {
            throw new SsoException(
                errorCode: 'token_expired',
                httpStatus: 401,
                publicMessage: 'Token akses SSO telah kedaluwarsa.',
                previous: $exception,
            );
        } catch (SignatureInvalidException|BeforeValidException|Throwable $exception) {
            throw new SsoException(
                errorCode: 'invalid_jwt',
                httpStatus: 401,
                publicMessage: 'Tanda tangan atau format token SSO tidak valid.',
                context: ['reason' => $exception->getMessage()],
                previous: $exception,
            );
        }

        $iss = rtrim((string) ($decoded->iss ?? ''), '/');
        $aud = (string) ($decoded->aud ?? '');
        $sub = (string) ($decoded->sub ?? '');
        $roleRaw = (string) ($decoded->role ?? '');

        if ($iss !== $this->baseUrl()) {
            throw new SsoException(
                errorCode: 'invalid_issuer',
                httpStatus: 401,
                publicMessage: 'Penerbit token SSO (iss) tidak sesuai.',
                context: ['iss' => $iss],
            );
        }

        if ($aud !== $this->clientId()) {
            throw new SsoException(
                errorCode: 'invalid_audience',
                httpStatus: 401,
                publicMessage: 'Token SSO tidak ditujukan untuk Modul 5.',
                context: ['aud' => $aud],
            );
        }

        if ($sub === '') {
            throw new SsoException(
                errorCode: 'missing_subject',
                httpStatus: 401,
                publicMessage: 'Identitas pengguna (sub) pada token SSO kosong.',
            );
        }

        $role = UserRole::tryFrom($roleRaw);

        if ($role === null) {
            throw new SsoException(
                errorCode: 'unsupported_role',
                httpStatus: 403,
                publicMessage: 'Peran akun Anda tidak diizinkan mengakses Modul Magang/SI.',
                context: ['role' => $roleRaw],
            );
        }

        $nip = isset($decoded->nip) && trim((string) $decoded->nip) !== ''
            ? trim((string) $decoded->nip)
            : null;

        return new SsoUserClaims(
            sub: $sub,
            name: (string) ($decoded->name ?? 'Pengguna'),
            email: (string) ($decoded->email ?? ''),
            nip: $nip,
            role: $role,
            iss: $iss,
            aud: $aud,
            iat: (int) ($decoded->iat ?? time()),
            exp: (int) ($decoded->exp ?? time()),
        );
    }

    /**
     * Query SACIBIRU /api/sso/userinfo for critical operations (e.g., SK approval or grade publication).
     *
     * @return array{sub: string, name: string, email: string, nip: ?string, role: UserRole}
     */
    public function fetchUserInfo(string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->timeout($this->httpTimeout())
                ->get($this->baseUrl().'/api/sso/userinfo');
        } catch (ConnectionException $exception) {
            throw new SsoException(
                errorCode: 'sso_unreachable',
                httpStatus: 503,
                publicMessage: 'Gagal memverifikasi sesi ke SSO SACIBIRU.',
                previous: $exception,
            );
        }

        if (! $response->ok()) {
            throw new SsoException(
                errorCode: 'invalid_token',
                httpStatus: 401,
                publicMessage: 'Sesi SSO Anda tidak lagi aktif. Silakan masuk kembali.',
                context: ['sso_http_status' => $response->status()],
            );
        }

        $data = $response->json();
        $sub = (string) ($data['sub'] ?? '');
        $role = UserRole::tryFrom((string) ($data['role'] ?? ''));

        if ($sub === '' || $role === null) {
            throw new SsoException(
                errorCode: 'unsupported_role',
                httpStatus: 403,
                publicMessage: 'Peran akun Anda tidak lagi memiliki akses ke Modul Magang/SI.',
                context: ['role' => $data['role'] ?? null],
            );
        }

        $nip = isset($data['nip']) && trim((string) $data['nip']) !== ''
            ? trim((string) $data['nip'])
            : null;

        return [
            'sub' => $sub,
            'name' => (string) ($data['name'] ?? 'Pengguna'),
            'email' => (string) ($data['email'] ?? ''),
            'nip' => $nip,
            'role' => $role,
        ];
    }

    /**
     * Validate and convert the raw /api/sso/token JSON response into a typed payload.
     */
    private function parseTokenResponse(mixed $payload): SsoTokenPayload
    {
        if (! is_array($payload)) {
            throw new SsoException(
                errorCode: 'invalid_token_response',
                httpStatus: 401,
                publicMessage: 'Format respons token dari SSO tidak valid.',
            );
        }

        $accessToken = (string) ($payload['access_token'] ?? '');
        $refreshToken = (string) ($payload['refresh_token'] ?? '');
        $expiresIn = (int) ($payload['expires_in'] ?? 900);

        if ($accessToken === '' || $refreshToken === '') {
            throw new SsoException(
                errorCode: 'incomplete_token_response',
                httpStatus: 401,
                publicMessage: 'Respons token dari SSO tidak lengkap.',
            );
        }

        $claims = $this->verifyAccessToken($accessToken);

        return new SsoTokenPayload(
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            expiresIn: $expiresIn,
            claims: $claims,
        );
    }

    /**
     * Decode the unverified JWT header to inspect alg and kid before signature verification.
     *
     * @return array<string, mixed>
     */
    private function decodeJwtHeader(string $jwt): array
    {
        $segments = explode('.', $jwt);

        if (count($segments) !== 3) {
            throw new SsoException(
                errorCode: 'invalid_jwt_format',
                httpStatus: 401,
                publicMessage: 'Format JWT tidak valid.',
            );
        }

        try {
            $decoded = json_decode(JWT::urlsafeB64Decode($segments[0]), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new SsoException(
                errorCode: 'invalid_jwt_header',
                httpStatus: 401,
                publicMessage: 'Header JWT tidak dapat dibaca.',
                previous: $exception,
            );
        }

        if (! is_array($decoded)) {
            throw new SsoException(
                errorCode: 'invalid_jwt_header',
                httpStatus: 401,
                publicMessage: 'Header JWT tidak valid.',
            );
        }

        return $decoded;
    }

    /**
     * Resolve RS256 public keys from cached JWKS, automatically refetching once if kid is unknown.
     *
     * @return array<string, Key>
     */
    private function resolveVerificationKeys(?string $kid): array
    {
        $keys = $this->parseRs256KeySet($this->fetchJwks(false));

        if ($kid !== null && ! array_key_exists($kid, $keys)) {
            $this->auditLogger->record(
                action: 'sso.jwks_refetched',
                properties: ['unknown_kid' => $kid],
                persistToDatabase: false,
            );

            $keys = $this->parseRs256KeySet($this->fetchJwks(true));
        }

        if ($keys === []) {
            throw new SsoException(
                errorCode: 'jwks_empty',
                httpStatus: 503,
                publicMessage: 'Kunci publik RS256 dari SSO tidak tersedia.',
            );
        }

        return $keys;
    }

    /**
     * Retrieve the JWKS JSON array from cache or the SSO endpoint.
     *
     * @return array<string, mixed>
     */
    private function fetchJwks(bool $forceRefresh): array
    {
        if ($forceRefresh) {
            Cache::forget(self::JWKS_CACHE_KEY);
        }

        $ttl = max(60, (int) config('sso.jwks_cache_ttl', 300));

        /** @var array<string, mixed> $jwks */
        $jwks = Cache::remember(self::JWKS_CACHE_KEY, $ttl, function (): array {
            try {
                $response = Http::acceptJson()
                    ->timeout($this->httpTimeout())
                    ->get($this->baseUrl().'/api/sso/jwks');
            } catch (ConnectionException $exception) {
                throw new SsoException(
                    errorCode: 'jwks_unavailable',
                    httpStatus: 503,
                    publicMessage: 'Gagal mengambil kunci publik (JWKS) dari SSO.',
                    previous: $exception,
                );
            }

            if (! $response->ok() || ! is_array($response->json())) {
                throw new SsoException(
                    errorCode: 'jwks_unavailable',
                    httpStatus: 503,
                    publicMessage: 'Endpoint JWKS SSO mengembalikan respons tidak valid.',
                    context: ['sso_http_status' => $response->status()],
                );
            }

            return $response->json();
        });

        return $jwks;
    }

    /**
     * Parse JWK set and restrict accepted verification keys strictly to RS256.
     *
     * @param  array<string, mixed>  $jwks
     * @return array<string, Key>
     */
    private function parseRs256KeySet(array $jwks): array
    {
        try {
            $parsed = JWK::parseKeySet($jwks, 'RS256');
        } catch (Throwable $exception) {
            throw new SsoException(
                errorCode: 'jwks_invalid',
                httpStatus: 503,
                publicMessage: 'Format kunci publik JWKS dari SSO tidak valid.',
                previous: $exception,
            );
        }

        return array_filter(
            $parsed,
            static fn (Key $key): bool => $key->getAlgorithm() === 'RS256',
        );
    }

    private function httpTimeout(): int
    {
        return max(3, (int) config('sso.http_timeout_seconds', 10));
    }
}
