<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuditLogger
{
    /**
     * Keys that must never be written to log files or the activity_logs table.
     *
     * @var list<string>
     */
    private const REDACTED_KEYS = [
        'client_secret',
        'code',
        'code_verifier',
        'access_token',
        'refresh_token',
        'refresh_token_encrypted',
        'access_token_encrypted',
        'authorization',
        'password',
        'token',
    ];

    /**
     * Record a security or business event to both the dedicated SSO log channel and activity_logs table.
     *
     * @param  array<string, mixed>  $properties
     */
    public function record(
        string $action,
        ?User $user = null,
        ?Request $request = null,
        array $properties = [],
        ?Model $subject = null,
        string $level = 'info',
        bool $persistToDatabase = true,
    ): void {
        $sanitized = $this->sanitize($properties);

        $logContext = array_filter([
            'action' => $action,
            'user_id' => $user?->id,
            'sso_sub' => $user?->sso_sub,
            'role' => $user?->role?->value,
            'ip' => $request?->ip(),
            'path' => $request?->path(),
            'properties' => $sanitized !== [] ? $sanitized : null,
        ], static fn (mixed $value): bool => $value !== null);

        Log::channel('sso')->log($level, $action, $logContext);

        if (! $persistToDatabase) {
            return;
        }

        ActivityLog::query()->create([
            'user_id' => $user?->id,
            'role' => $user?->role?->value,
            'action' => $action,
            'subject_type' => $subject !== null ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $sanitized !== [] ? $sanitized : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Recursively remove sensitive keys from structured audit metadata.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function sanitize(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if (in_array($normalizedKey, self::REDACTED_KEYS, true)) {
                continue;
            }

            if (is_array($value)) {
                $clean[$key] = $this->sanitize($value);
            } else {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }
}
