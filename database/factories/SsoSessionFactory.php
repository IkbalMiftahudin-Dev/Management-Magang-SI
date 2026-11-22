<?php

namespace Database\Factories;

use App\Models\SsoSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * @extends Factory<SsoSession>
 */
class SsoSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'session_id' => Str::random(40),
            'refresh_token_encrypted' => Crypt::encryptString('refresh_'.Str::random(32)),
            'access_token_encrypted' => Crypt::encryptString('access_'.Str::random(32)),
            'access_token_expires_at' => now()->addMinutes(15),
            'last_refreshed_at' => now(),
        ];
    }

    /**
     * Mark the SSO access token as expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'access_token_expires_at' => now()->subMinute(),
        ]);
    }
}
