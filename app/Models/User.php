<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'sso_sub',
    'name',
    'email',
    'identifier_number',
    'role',
    'password',
    'last_sso_sync_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_sso_sync_at' => 'datetime',
            'role' => UserRole::class,
            'password' => 'hashed',
        ];
    }

    /**
     * SSO sessions tied to this user across browsers/devices.
     *
     * @return HasMany<SsoSession, $this>
     */
    public function ssoSessions(): HasMany
    {
        return $this->hasMany(SsoSession::class);
    }

    /**
     * Audit trail entries authored by this user.
     *
     * @return HasMany<ActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Determine whether the user holds at least one of the specified roles.
     */
    public function hasRole(UserRole|string ...$roles): bool
    {
        if ($this->role === null) {
            return false;
        }

        foreach ($roles as $candidate) {
            $resolved = UserRole::fromInput($candidate);

            if ($resolved !== null && $this->role === $resolved) {
                return true;
            }
        }

        return false;
    }

    /**
     * Two-letter uppercase initials for the profile avatar badge.
     */
    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $letters = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            if ($part !== '') {
                $letters .= mb_strtoupper(mb_substr($part, 0, 1));
            }
        }

        return $letters !== '' ? $letters : 'US';
    }
}
