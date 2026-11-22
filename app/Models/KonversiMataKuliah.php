<?php

namespace App\Models;

use App\Enums\StatusPenilaian;
use App\Enums\TingkatKesesuaian;
use Database\Factories\KonversiMataKuliahFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'pendataan_magang_id',
    'mata_kuliah_id',
    'dosen_penilai_id',
    'sks_konversi',
    'tingkat_kesesuaian',
    'nilai_instansi',
    'nilai_dosen',
    'nilai_akhir',
    'nilai_huruf',
    'catatan_dosen',
    'status_penilaian',
    'is_locked',
    'published_at',
])]
class KonversiMataKuliah extends Model
{
    /** @use HasFactory<KonversiMataKuliahFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sks_konversi' => 'integer',
            'tingkat_kesesuaian' => TingkatKesesuaian::class,
            'status_penilaian' => StatusPenilaian::class,
            'nilai_instansi' => 'decimal:2',
            'nilai_dosen' => 'decimal:2',
            'nilai_akhir' => 'decimal:2',
            'is_locked' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PendataanMagang, $this>
     */
    public function pendataanMagang(): BelongsTo
    {
        return $this->belongsTo(PendataanMagang::class);
    }

    /**
     * @return BelongsTo<MataKuliah, $this>
     */
    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class);
    }

    /**
     * @return BelongsTo<Dosen, $this>
     */
    public function dosenPenilai(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_penilai_id');
    }

    /**
     * @return HasMany<PenilaianKonversiDetail, $this>
     */
    public function penilaianDetails(): HasMany
    {
        return $this->hasMany(PenilaianKonversiDetail::class);
    }

    /**
     * Calculate the weighted lecturer score (0..100) from rubric component evaluations.
     */
    public function calculateWeightedScore(): ?float
    {
        $details = $this->relationLoaded('penilaianDetails')
            ? $this->penilaianDetails
            : $this->penilaianDetails()->get();

        if ($details->isEmpty()) {
            return null;
        }

        $totalWeight = (int) $details->sum('bobot_persen_snapshot');

        if ($totalWeight <= 0) {
            return null;
        }

        $weightedSum = 0.0;

        foreach ($details as $detail) {
            $weightedSum += ((float) $detail->nilai) * ((int) $detail->bobot_persen_snapshot);
        }

        return round($weightedSum / $totalWeight, 2);
    }

    /**
     * Convert a numeric score (0..100) into the standard UPI academic letter grade.
     */
    public static function scoreToGradeLetter(float $score): string
    {
        return match (true) {
            $score >= 85.0 => 'A',
            $score >= 80.0 => 'A-',
            $score >= 75.0 => 'B+',
            $score >= 70.0 => 'B',
            $score >= 65.0 => 'B-',
            $score >= 60.0 => 'C+',
            $score >= 55.0 => 'C',
            $score >= 40.0 => 'D',
            default => 'E',
        };
    }
}
