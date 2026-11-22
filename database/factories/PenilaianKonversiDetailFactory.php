<?php

namespace Database\Factories;

use App\Models\KonversiMataKuliah;
use App\Models\PenilaianKonversiDetail;
use App\Models\RubrikKomponen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PenilaianKonversiDetail>
 */
class PenilaianKonversiDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'konversi_mata_kuliah_id' => KonversiMataKuliah::factory(),
            'rubrik_komponen_id' => RubrikKomponen::factory(),
            'bobot_persen_snapshot' => 25,
            'nilai' => fake()->randomFloat(2, 75, 95),
        ];
    }
}
