<?php

namespace Database\Factories;

use App\Enums\StatusValidasiLogbook;
use App\Enums\TipeLogbook;
use App\Models\Logbook;
use App\Models\PendataanMagang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Logbook>
 */
class LogbookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pendataan_magang_id' => PendataanMagang::factory()->verified(),
            'tipe_pengisian' => TipeLogbook::Mingguan,
            'tanggal' => now()->toDateString(),
            'minggu_ke' => fake()->numberBetween(1, 16),
            'deskripsi_kegiatan' => fake()->paragraph(),
            'foto_bukti_path' => 'private/logbooks/bukti-sample.jpg',
            'status_validasi' => StatusValidasiLogbook::Menunggu,
            'catatan_dosen' => null,
            'validated_by' => null,
            'validated_at' => null,
        ];
    }
}
