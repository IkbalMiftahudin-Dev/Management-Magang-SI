<?php

namespace Database\Factories;

use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mahasiswa>
 */
class MahasiswaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->mahasiswa(),
            'nim' => (string) fake()->unique()->numerify('240####'),
            'nama' => fake()->name(),
            'prodi' => 'Rekayasa Perangkat Lunak',
            'angkatan' => 2024,
            'sks_lulus' => fake()->numberBetween(90, 130),
        ];
    }
}
