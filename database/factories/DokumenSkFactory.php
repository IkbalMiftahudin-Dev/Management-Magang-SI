<?php

namespace Database\Factories;

use App\Enums\JenisSk;
use App\Enums\StatusPengesahanSk;
use App\Models\DokumenSk;
use App\Models\PendataanMagang;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DokumenSk>
 */
class DokumenSkFactory extends Factory
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
            'jenis_sk' => JenisSk::SkMagang,
            'nomor_sk' => sprintf('SK/RPL/MG/%d/%04d', now()->year, fake()->unique()->numberBetween(1, 9999)),
            'file_path' => 'private/sk/sk-magang-sample.pdf',
            'checksum_sha256' => hash('sha256', fake()->uuid()),
            'status_pengesahan' => StatusPengesahanSk::Draft,
            'tanggal_diterbitkan' => now()->toDateString(),
            'generated_by' => User::factory()->staffKaprodi(),
            'approved_by' => null,
            'approved_at' => null,
        ];
    }
}
