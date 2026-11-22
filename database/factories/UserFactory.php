<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sso_sub' => (string) Str::uuid(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'identifier_number' => (string) fake()->unique()->numerify('240####'),
            'role' => UserRole::Mahasiswa,
            'email_verified_at' => now(),
            'password' => null,
            'remember_token' => Str::random(10),
            'last_sso_sync_at' => now(),
        ];
    }

    /**
     * Configure the user with a specific SSO role.
     */
    public function withRole(UserRole $role): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => $role,
            'identifier_number' => $role === UserRole::Mahasiswa
                ? (string) fake()->unique()->numerify('240####')
                : (string) fake()->unique()->numerify('1990########00#'),
        ]);
    }

    public function mahasiswa(): static
    {
        return $this->withRole(UserRole::Mahasiswa);
    }

    public function dosen(): static
    {
        return $this->withRole(UserRole::Dosen);
    }

    public function staffKaprodi(): static
    {
        return $this->withRole(UserRole::StaffKaprodi);
    }

    public function kaprodi(): static
    {
        return $this->withRole(UserRole::Kaprodi);
    }

    public function admin(): static
    {
        return $this->withRole(UserRole::Admin);
    }
}
