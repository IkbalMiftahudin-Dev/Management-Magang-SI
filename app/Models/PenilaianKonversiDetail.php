<?php

namespace App\Models;

use Database\Factories\PenilaianKonversiDetailFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'konversi_mata_kuliah_id',
    'rubrik_komponen_id',
    'bobot_persen_snapshot',
    'nilai',
])]
class PenilaianKonversiDetail extends Model
{
    /** @use HasFactory<PenilaianKonversiDetailFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bobot_persen_snapshot' => 'integer',
            'nilai' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<KonversiMataKuliah, $this>
     */
    public function konversiMataKuliah(): BelongsTo
    {
        return $this->belongsTo(KonversiMataKuliah::class);
    }

    /**
     * @return BelongsTo<RubrikKomponen, $this>
     */
    public function rubrikKomponen(): BelongsTo
    {
        return $this->belongsTo(RubrikKomponen::class);
    }
}
