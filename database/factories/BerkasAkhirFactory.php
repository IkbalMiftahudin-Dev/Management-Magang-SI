<?php

namespace Database\Factories;

use App\Enums\StatusBerkasAkhir;
use App\Models\BerkasAkhir;
use App\Models\PendataanMagang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BerkasAkhir>
 */
class BerkasAkhirFactory extends Factory
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
            'loa_path' => 'private/berkas/loa.pdf',
            'loa_uploaded_at' => now(),
            'rubrik_konversi_path' => 'private/berkas/rubrik.pdf',
            'rubrik_konversi_uploaded_at' => now(),
            'laporan_akhir_path' => null,
            'laporan_akhir_uploaded_at' => null,
            'draf_pks_path' => null,
            'draf_pks_uploaded_at' => null,
            'transkrip_instansi_path' => 'private/berkas/transkrip.pdf',
            'transkrip_instansi_uploaded_at' => now(),
            'sertifikat_path' => null,
            'sertifikat_uploaded_at' => null,
            'status_kelengkapan' => StatusBerkasAkhir::BelumLengkap,
            'submitted_at' => null,
        ];
    }

    /**
     * Populate all 5 mandatory documents (without optional certificate).
     */
    public function mandatoryComplete(): static
    {
        return $this->state(fn (array $attributes) => [
            'loa_path' => 'private/berkas/loa.pdf',
            'loa_uploaded_at' => now(),
            'rubrik_konversi_path' => 'private/berkas/rubrik.pdf',
            'rubrik_konversi_uploaded_at' => now(),
            'laporan_akhir_path' => 'private/berkas/laporan.pdf',
            'laporan_akhir_uploaded_at' => now(),
            'draf_pks_path' => 'private/berkas/pks.pdf',
            'draf_pks_uploaded_at' => now(),
            'transkrip_instansi_path' => 'private/berkas/transkrip.pdf',
            'transkrip_instansi_uploaded_at' => now(),
            'status_kelengkapan' => StatusBerkasAkhir::SiapDinilai,
            'submitted_at' => now(),
        ]);
    }
}
