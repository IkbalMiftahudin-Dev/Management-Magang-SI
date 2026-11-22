<?php

namespace Database\Factories;

use App\Enums\JenisProgramMagang;
use App\Enums\ModeKegiatan;
use App\Enums\StatusPendataan;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\PendataanMagang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PendataanMagang>
 */
class PendataanMagangFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mahasiswa_id' => Mahasiswa::factory(),
            'dosen_pembimbing_id' => Dosen::factory(),
            'dosen_kunjung_id' => null,
            'semester_akademik' => 'Semester Genap 2027/2028',
            'nama_instansi' => 'Telkom Indonesia',
            'posisi' => 'UI/UX Designer Intern',
            'alamat_instansi' => 'Jl. Japati No. 1, Bandung, Jawa Barat',
            'kontak_instansi' => 'talent@telkom.co.id',
            'jenis_program' => JenisProgramMagang::Msib,
            'mode_kegiatan' => ModeKegiatan::Hybrid,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addWeeks(24)->toDateString(),
            'loa_path' => 'private/loa/sample-loa.pdf',
            'loa_uploaded_at' => now(),
            'current_step' => 4,
            'pernyataan_disetujui' => true,
            'status_pendataan' => StatusPendataan::MenungguVerifikasi,
            'catatan_verifikasi' => null,
            'submitted_at' => now(),
            'verified_at' => null,
            'verified_by' => null,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_pendataan' => StatusPendataan::Terverifikasi,
            'verified_at' => now(),
        ]);
    }
}
