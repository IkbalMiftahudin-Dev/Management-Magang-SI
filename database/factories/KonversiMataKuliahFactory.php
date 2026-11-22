<?php

namespace Database\Factories;

use App\Enums\StatusPenilaian;
use App\Enums\TingkatKesesuaian;
use App\Models\Dosen;
use App\Models\KonversiMataKuliah;
use App\Models\MataKuliah;
use App\Models\PendataanMagang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KonversiMataKuliah>
 */
class KonversiMataKuliahFactory extends Factory
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
            'mata_kuliah_id' => MataKuliah::factory(),
            'dosen_penilai_id' => Dosen::factory(),
            'sks_konversi' => 3,
            'tingkat_kesesuaian' => TingkatKesesuaian::SangatSesuai,
            'nilai_instansi' => null,
            'nilai_dosen' => null,
            'nilai_akhir' => null,
            'nilai_huruf' => null,
            'catatan_dosen' => null,
            'status_penilaian' => StatusPenilaian::BelumDinilai,
            'is_locked' => false,
            'published_at' => null,
        ];
    }
}
