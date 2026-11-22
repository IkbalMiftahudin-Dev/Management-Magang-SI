<?php

namespace App\Services;

use App\Data\SsoTokenPayload;
use App\Exceptions\SsoException;
use App\Models\SsoSession;
use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class SsoSessionManager
{
    public const SESSION_TOKEN_KEY = 'sso_session_key';

    public function __construct(
        private readonly SsoService $ssoService,
        private readonly SsoUserSyncService $userSyncService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Persist encrypted SSO tokens bound to a dedicated opaque key stored inside the Laravel session.
     */
    public function store(Request $request, User $user, SsoTokenPayload $payload): SsoSession
    {
        $existingKey = $request->session()->get(self::SESSION_TOKEN_KEY);

        if (is_string($existingKey) && $existingKey !== '') {
            SsoSession::query()->where('session_id', $existingKey)->delete();
        }

        $sessionKey = (string) Str::uuid();

        $ssoSession = SsoSession::query()->create([
            'user_id' => $user->id,
            'session_id' => $sessionKey,
            'refresh_token_encrypted' => Crypt::encryptString($payload->refreshToken),
            'access_token_encrypted' => Crypt::encryptString($payload->accessToken),
            'access_token_expires_at' => $payload->claims->expiresAt(),
            'last_refreshed_at' => now(),
        ]);

        $request->session()->put(self::SESSION_TOKEN_KEY, $sessionKey);

        return $ssoSession;
    }

    /**
     * Retrieve the SsoSession tied to the current HTTP session and authenticated user.
     */
    public function findForRequest(Request $request, User $user): ?SsoSession
    {
        $sessionKey = $request->session()->get(self::SESSION_TOKEN_KEY);

        if (! is_string($sessionKey) || $sessionKey === '') {
            return null;
        }

        return SsoSession::query()
            ->where('session_id', $sessionKey)
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Ensure the current SSO session is valid, rotating tokens inside an atomic lock when needed.
     */
    public function ensureValidSession(Request $request, User $user): bool
    {
        $ssoSession = $this->findForRequest($request, $user);

        if ($ssoSession === null) {
            return false;
        }

        $leeway = max(0, (int) config('sso.refresh_leeway_seconds', 60));

        if (! $ssoSession->needsRefresh($leeway)) {
            return true;
        }

        $lockKey = 'sso:refresh:'.$ssoSession->id;

        try {
            return (bool) Cache::lock($lockKey, 10)->block(5, function () use ($ssoSession, $leeway, $request, $user): bool {
                $freshSession = SsoSession::query()->find($ssoSession->id);

                if ($freshSession === null) {
                    return false;
                }

                if (! $freshSession->needsRefresh($leeway)) {
                    return true;
                }

                $payload = $this->ssoService->refreshTokens($freshSession->decryptedRefreshToken());

                $freshSession->forceFill([
                    'refresh_token_encrypted' => Crypt::encryptString($payload->refreshToken),
                    'access_token_encrypted' => Crypt::encryptString($payload->accessToken),
                    'access_token_expires_at' => $payload->claims->expiresAt(),
                    'last_refreshed_at' => now(),
                ])->save();

                $previousRole = $user->role?->value;
                $syncedUser = $this->userSyncService->syncFromClaims($payload->claims);
                Auth::setUser($syncedUser);

                $this->auditLogger->record(
                    action: 'sso.token_refreshed',
                    user: $syncedUser,
                    request: $request,
                    properties: [
                        'previous_role' => $previousRole,
                        'new_role' => $syncedUser->role?->value,
                    ],
                    persistToDatabase: $previousRole !== $syncedUser->role?->value,
                );

                return true;
            });
        } catch (SsoException $exception) {
            $freshSession = SsoSession::query()->find($ssoSession->id);

            if ($freshSession !== null && in_array($exception->errorCode, ['invalid_grant', 'invalid_token', 'unsupported_role', 'invalid_client'], true)) {
                $freshSession->delete();
            }

            $this->auditLogger->record(
                action: 'sso.session_revoked',
                user: $user,
                request: $request,
                properties: ['error_code' => $exception->errorCode],
                level: 'warning',
            );

            return false;
        } catch (LockTimeoutException) {
            $reloaded = SsoSession::query()->find($ssoSession->id);

            return $reloaded !== null && ! $reloaded->needsRefresh($leeway);
        }
    }

    /**
     * Verify live session state against SACIBIRU /api/sso/userinfo for critical actions.
     */
    public function verifyFreshUserInfo(Request $request, User $user): bool
    {
        if (! $this->ensureValidSession($request, $user)) {
            return false;
        }

        $ssoSession = $this->findForRequest($request, $user);
        $accessToken = $ssoSession?->decryptedAccessToken();

        if ($ssoSession === null || $accessToken === null) {
            return false;
        }

        try {
            $userInfo = $this->ssoService->fetchUserInfo($accessToken);
            $syncedUser = $this->userSyncService->syncFromUserInfo($userInfo);
            Auth::setUser($syncedUser);

            return true;
        } catch (SsoException $exception) {
            $ssoSession->delete();

            $this->auditLogger->record(
                action: 'sso.userinfo_failed',
                user: $user,
                request: $request,
                properties: ['error_code' => $exception->errorCode],
                level: 'warning',
            );

            return false;
        }
    }

    /**
     * Destroy the local SSO session record and invalidate the Laravel session.
     */
    public function terminateLocalSession(Request $request, ?User $user = null): void
    {
        $sessionKey = $request->session()->get(self::SESSION_TOKEN_KEY);

        if (is_string($sessionKey) && $sessionKey !== '') {
            SsoSession::query()->where('session_id', $sessionKey)->delete();
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
