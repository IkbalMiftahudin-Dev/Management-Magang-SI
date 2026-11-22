<?php

namespace Tests\Support;

use Firebase\JWT\JWT;
use OpenSSLAsymmetricKey;
use RuntimeException;

trait InteractsWithFakeSso
{
    /**
     * Configure deterministic test SSO settings.
     */
    protected function configureTestSso(): void
    {
        config([
            'sso.url' => 'https://sso.test',
            'sso.client_id' => 'modul-magang',
            'sso.client_secret' => 'super-secret-client-value',
            'sso.redirect_uri' => 'http://localhost/auth/callback',
            'sso.post_logout_redirect_uri' => 'http://localhost/logged-out',
            'sso.jwks_cache_ttl' => 300,
            'sso.refresh_leeway_seconds' => 60,
            'sso.http_timeout_seconds' => 5,
        ]);
    }

    /**
     * Generate a real ephemeral RSA-2048 keypair and corresponding JWK structure for RS256 testing.
     *
     * @return array{private_pem: string, jwk: array<string, string>, kid: string}
     */
    protected function generateRsaKeyPair(string $kid = 'test-kid-1'): array
    {
        $options = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $resource = openssl_pkey_new($options);

        if ($resource === false) {
            $configPath = $this->findWindowsOpenSslConfig();

            if ($configPath !== null) {
                $options['config'] = $configPath;
                $resource = openssl_pkey_new($options);
            }
        }

        if (! $resource instanceof OpenSSLAsymmetricKey) {
            throw new RuntimeException('Unable to generate RSA keypair for SSO tests: '.openssl_error_string());
        }

        $exportOptions = isset($options['config']) ? ['config' => $options['config']] : null;
        $exported = openssl_pkey_export($resource, $privatePem, null, $exportOptions);

        if (! $exported || ! is_string($privatePem)) {
            throw new RuntimeException('Unable to export RSA private key for SSO tests.');
        }

        $details = openssl_pkey_get_details($resource);

        if (! is_array($details) || ! isset($details['rsa']['n'], $details['rsa']['e'])) {
            throw new RuntimeException('Unable to extract RSA public parameters for JWK.');
        }

        return [
            'private_pem' => $privatePem,
            'kid' => $kid,
            'jwk' => [
                'kty' => 'RSA',
                'n' => $this->base64UrlEncode($details['rsa']['n']),
                'e' => $this->base64UrlEncode($details['rsa']['e']),
                'kid' => $kid,
                'alg' => 'RS256',
                'use' => 'sig',
            ],
        ];
    }

    /**
     * Sign a realistic SACIBIRU RS256 JWT access token.
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function signSsoAccessToken(string $privatePem, string $kid, array $overrides = []): string
    {
        $now = time();

        $payload = array_merge([
            'iss' => config('sso.url'),
            'aud' => config('sso.client_id'),
            'sub' => '6b1f0c52-0000-4000-8000-000000000001',
            'name' => 'Mohamad Firdiansyah',
            'email' => 'firdiansyah@upi.edu',
            'nip' => '2402041',
            'role' => 'Mahasiswa',
            'iat' => $now,
            'exp' => $now + 900,
        ], $overrides);

        return JWT::encode($payload, $privatePem, 'RS256', $kid);
    }

    private function base64UrlEncode(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }

    private function findWindowsOpenSslConfig(): ?string
    {
        $phpDir = dirname(PHP_BINARY);
        $candidates = [
            $phpDir.'/extras/ssl/openssl.cnf',
            $phpDir.'/openssl.cnf',
            'C:/xampp/php/extras/ssl/openssl.cnf',
            'C:/xampp/apache/conf/openssl.cnf',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
