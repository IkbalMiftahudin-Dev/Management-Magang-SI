<?php

namespace App\Models;

use Database\Factories\RubrikKomponenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'mata_kuliah_id',
    'nama_komponen',
    'deskripsi',
    'bobot_persen',
    'urutan',
])]
class RubrikKomponen extends Model
{
    /** @use HasFactory<RubrikKomponenFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bobot_persen' => 'integer',
            'urutan' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<MataKuliah, $this>
     */
    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class);
    }

    /**
     * @return HasMany<PenilaianKonversiDetail, $this>
     */
    public function penilaianDetails(): HasMany
    {
        return $this->hasMany(PenilaianKonversiDetail::class);
    }
}
