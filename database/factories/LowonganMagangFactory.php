<?php

namespace Database\Factories;

use App\Enums\StatusLowongan;
use App\Enums\SumberLowongan;
use App\Models\Dosen;
use App\Models\LowonganMagang;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LowonganMagang>
 */
class LowonganMagangFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'judul_lowongan' => fake()->jobTitle().' Intern',
            'nama_instansi' => fake()->company(),
            'posisi_bidang' => fake()->randomElement(['Product Design', 'Web Engineering', 'Infrastructure', 'Software Quality']),
            'lokasi_mode' => fake()->randomElement(['Bandung · Hybrid', 'Jakarta · Remote', 'Jakarta · On-site']),
            'sumber_lowongan' => fake()->randomElement(SumberLowongan::cases()),
            'kontak' => fake()->companyEmail(),
            'deskripsi' => fake()->paragraph(),
            'persyaratan' => fake()->sentence(12),
            'logo_path' => null,
            'jumlah_pelamar' => fake()->numberBetween(5, 35),
            'tanggal_posting' => now()->toDateString(),
            'batas_waktu' => now()->addMonths(2)->toDateString(),
            'status' => StatusLowongan::Aktif,
            'dosen_penanggung_jawab_id' => Dosen::factory(),
            'created_by' => User::factory()->staffKaprodi(),
        ];
    }
}
