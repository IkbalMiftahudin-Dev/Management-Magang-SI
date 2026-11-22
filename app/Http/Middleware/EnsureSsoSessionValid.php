<?php

namespace App\Http\Middleware;

use App\Services\SsoSessionManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSsoSessionValid
{
    public function __construct(
        private readonly SsoSessionManager $sessionManager,
    ) {}

    /**
     * Verify the active SSO session and refresh expired tokens atomically.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if (! $this->sessionManager->ensureValidSession($request, $user)) {
            $returnTo = $request->isMethod('GET') ? $request->getRequestUri() : null;

            $this->sessionManager->terminateLocalSession($request, $user);

            $loginParams = $returnTo !== null ? ['return_to' => $returnTo] : [];

            return redirect()->route('login', $loginParams);
        }

        return $next($request);
    }
}
