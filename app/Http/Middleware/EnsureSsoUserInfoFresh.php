<?php

namespace App\Http\Middleware;

use App\Services\SsoSessionManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSsoUserInfoFresh
{
    public function __construct(
        private readonly SsoSessionManager $sessionManager,
    ) {}

    /**
     * Perform a live /api/sso/userinfo check before allowing high-impact academic actions.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if (! $this->sessionManager->verifyFreshUserInfo($request, $user)) {
            $this->sessionManager->terminateLocalSession($request, $user);

            return redirect()->route('login');
        }

        return $next($request);
    }
}
