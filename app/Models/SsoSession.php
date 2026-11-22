<?php

namespace App\Models;

use Database\Factories\SsoSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

#[Fillable([
    'user_id',
    'session_id',
    'refresh_token_encrypted',
    'access_token_encrypted',
    'access_token_expires_at',
    'last_refreshed_at',
])]
#[Hidden(['refresh_token_encrypted', 'access_token_encrypted'])]
class SsoSession extends Model
{
    /** @use HasFactory<SsoSessionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'access_token_expires_at' => 'datetime',
            'last_refreshed_at' => 'datetime',
        ];
    }

    /**
     * Owner of this SSO session.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Decrypt the stored refresh token.
     */
    public function decryptedRefreshToken(): string
    {
        return Crypt::decryptString($this->refresh_token_encrypted);
    }

    /**
     * Decrypt the stored access token when present.
     */
    public function decryptedAccessToken(): ?string
    {
        if ($this->access_token_encrypted === null || $this->access_token_encrypted === '') {
            return null;
        }

        return Crypt::decryptString($this->access_token_encrypted);
    }

    /**
     * Check whether the access token has expired or is within the refresh leeway window.
     */
    public function needsRefresh(int $leewaySeconds = 60): bool
    {
        return $this->access_token_expires_at->copy()->subSeconds($leewaySeconds)->isPast();
    }
}
