<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\SsoException;
use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\SsoService;
use App\Services\SsoSessionManager;
use App\Services\SsoUserSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SsoController extends Controller
{
    public function __construct(
        private readonly SsoService $ssoService,
        private readonly SsoUserSyncService $userSyncService,
        private readonly SsoSessionManager $sessionManager,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Initiate OAuth 2.0 Authorization Code + PKCE login with SACIBIRU SSO.
     */
    public function login(Request $request): RedirectResponse
    {
        $state = $this->ssoService->generateState();
        $pkce = $this->ssoService->generatePkcePair();
        $returnTo = $this->resolveIntendedReturnPath($request);

        $request->session()->put([
            'sso_state' => $state,
            'sso_verifier' => $pkce['verifier'],
            'sso_return_to' => $returnTo,
        ]);

        $this->auditLogger->record(
            action: 'sso.login_initiated',
            request: $request,
            properties: ['has_return_to' => $returnTo !== null],
            persistToDatabase: false,
        );

        return redirect()->away(
            $this->ssoService->buildAuthorizeUrl($state, $pkce['challenge'])
        );
    }

    /**
     * Handle the OAuth callback from SACIBIRU SSO, verify RS256 JWT, and establish local session.
     */
    public function callback(Request $request): RedirectResponse|Response
    {
        $expectedState = (string) $request->session()->pull('sso_state', '');
        $receivedState = (string) $request->query('state', '');
        $codeVerifier = (string) $request->session()->pull('sso_verifier', '');
        $rawReturnTo = $request->session()->pull('sso_return_to');
        $safeTarget = $this->ssoService->sanitizeReturnPath(is_string($rawReturnTo) ? $rawReturnTo : null);

        if ($expectedState === '' || $receivedState === '' || ! hash_equals($expectedState, $receivedState)) {
            $this->auditLogger->record(
                action: 'sso.state_mismatch',
                request: $request,
                level: 'warning',
            );

            return response()->view('auth.error', [
                'status' => 400,
                'title' => 'Sesi Login Tidak Valid',
                'message' => 'Parameter keamanan (state) tidak cocok atau sudah kedaluwarsa. Silakan mulai login ulang.',
                'ssoMenuUrl' => $this->safeSsoMenuUrl(),
            ], 400);
        }

        $oauthError = (string) $request->query('error', '');

        if ($oauthError !== '') {
            $description = (string) $request->query('error_description', 'Peran akun Anda tidak diizinkan mengakses Modul Magang/SI.');

            $this->auditLogger->record(
                action: 'sso.callback_error',
                request: $request,
                properties: ['oauth_error' => $oauthError],
                level: 'warning',
            );

            $status = $oauthError === 'access_denied' ? 403 : 400;

            return response()->view('auth.error', [
                'status' => $status,
                'title' => $status === 403 ? 'Akses Modul Ditolak' : 'Autentikasi SSO Gagal',
                'message' => $description,
                'ssoMenuUrl' => $this->safeSsoMenuUrl(),
            ], $status);
        }

        $code = (string) $request->query('code', '');

        if ($code === '' || $codeVerifier === '') {
            $this->auditLogger->record(
                action: 'sso.callback_missing_code',
                request: $request,
                level: 'warning',
            );

            return response()->view('auth.error', [
                'status' => 400,
                'title' => 'Kode Otorisasi Tidak Ditemukan',
                'message' => 'Permintaan callback SSO tidak membawa kode otorisasi yang lengkap. Silakan coba masuk kembali.',
                'ssoMenuUrl' => $this->safeSsoMenuUrl(),
            ], 400);
        }

        try {
            $tokenPayload = $this->ssoService->exchangeAuthorizationCode($code, $codeVerifier);
        } catch (SsoException $exception) {
            $this->auditLogger->record(
                action: 'sso.callback_failed',
                request: $request,
                properties: array_merge(['error_code' => $exception->errorCode], $exception->context),
                level: 'warning',
            );

            return response()->view('auth.error', [
                'status' => $exception->httpStatus,
                'title' => $exception->httpStatus === 403 ? 'Akses Modul Ditolak' : 'Login SSO Gagal',
                'message' => $exception->publicMessage,
                'ssoMenuUrl' => $this->safeSsoMenuUrl(),
            ], $exception->httpStatus);
        }

        $user = $this->userSyncService->syncFromClaims($tokenPayload->claims);

        $request->session()->forget('url.intended');
        Auth::login($user);
        $request->session()->regenerate();

        $this->sessionManager->store($request, $user, $tokenPayload);

        $this->auditLogger->record(
            action: 'sso.login_success',
            user: $user,
            request: $request,
            properties: ['role' => $user->role->value],
        );

        if ($safeTarget !== null) {
            return redirect($safeTarget);
        }

        return redirect()->route($user->role->dashboardRoute());
    }

    /**
     * Destroy local session and redirect browser to SACIBIRU Single Logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();

        $this->auditLogger->record(
            action: 'sso.logout',
            user: $user,
            request: $request,
        );

        $this->sessionManager->terminateLocalSession($request, $user);

        return redirect()->away($this->ssoService->buildLogoutUrl());
    }

    /**
     * Public post-logout confirmation screen preventing redirect loops on auth-only root.
     */
    public function loggedOut(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route($request->user()->role->dashboardRoute());
        }

        return view('auth.logged-out', [
            'ssoMenuUrl' => $this->safeSsoMenuUrl(),
        ]);
    }

    /**
     * Extract and sanitize return_to from query string or Laravel url.intended session value.
     */
    private function resolveIntendedReturnPath(Request $request): ?string
    {
        $candidate = $request->query('return_to');

        if (! is_string($candidate) || trim($candidate) === '') {
            $intendedUrl = $request->session()->get('url.intended');

            if (is_string($intendedUrl) && $intendedUrl !== '') {
                $parsed = parse_url($intendedUrl);

                if ($parsed !== false) {
                    $path = $parsed['path'] ?? '/';
                    $query = isset($parsed['query']) ? '?'.$parsed['query'] : '';
                    $candidate = $path.$query;
                }
            }
        }

        return $this->ssoService->sanitizeReturnPath(is_string($candidate) ? $candidate : null);
    }

    /**
     * Build the SACIBIRU main menu URL safely when configured.
     */
    private function safeSsoMenuUrl(): ?string
    {
        $base = rtrim((string) config('sso.url', ''), '/');

        return $base !== '' ? $base.'/menu' : null;
    }
}
