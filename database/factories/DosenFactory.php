<?php

namespace Database\Factories;

use App\Models\Dosen;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dosen>
 */
class DosenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->dosen(),
            'nip' => (string) fake()->unique()->numerify('198#########00#'),
            'nama' => fake()->name().', M.T.',
            'email' => fake()->unique()->safeEmail(),
            'bidang_kepakaran' => fake()->randomElement([
                'Rekayasa Perangkat Lunak',
                'Cyber Security',
                'Interaksi Manusia Komputer',
                'Manajemen Proyek TI',
                'Pengembangan Aplikasi Web',
            ]),
        ];
    }
}
