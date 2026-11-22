<?php

namespace Database\Factories;

use App\Models\MataKuliah;
use App\Models\RubrikKomponen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RubrikKomponen>
 */
class RubrikKomponenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mata_kuliah_id' => MataKuliah::factory(),
            'nama_komponen' => fake()->sentence(3),
            'deskripsi' => fake()->sentence(6),
            'bobot_persen' => 25,
            'urutan' => 1,
        ];
    }
}
