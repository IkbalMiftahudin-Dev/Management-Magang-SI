<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Restrict access strictly to users whose SSO role matches one of the allowed roles.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if (! $user->hasRole(...$roles)) {
            $this->auditLogger->record(
                action: 'rbac.forbidden',
                user: $user,
                request: $request,
                properties: [
                    'required_roles' => $roles,
                    'actual_role' => $user->role?->value,
                ],
                level: 'warning',
            );

            abort(403, 'Anda tidak memiliki hak akses untuk membuka halaman ini.');
        }

        return $next($request);
    }
}
