<?php

namespace Database\Factories;

use App\Models\Dosen;
use App\Models\MataKuliah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MataKuliah>
 */
class MataKuliahFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode_mk' => strtoupper(fake()->unique()->bothify('IF###')),
            'nama_mk' => fake()->words(3, true),
            'sks' => fake()->randomElement([2, 3, 4]),
            'dosen_pengampu_id' => Dosen::factory(),
            'deskripsi' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
