<?php

namespace App\Models;

use Database\Factories\MataKuliahFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'kode_mk',
    'nama_mk',
    'sks',
    'dosen_pengampu_id',
    'deskripsi',
    'is_active',
])]
class MataKuliah extends Model
{
    /** @use HasFactory<MataKuliahFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sks' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Dosen, $this>
     */
    public function dosenPengampu(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_pengampu_id');
    }

    /**
     * @return HasMany<RubrikKomponen, $this>
     */
    public function rubrikKomponens(): HasMany
    {
        return $this->hasMany(RubrikKomponen::class)->orderBy('urutan');
    }

    /**
     * @return HasMany<KonversiMataKuliah, $this>
     */
    public function konversiMataKuliahs(): HasMany
    {
        return $this->hasMany(KonversiMataKuliah::class);
    }

    /**
     * Total bobot persen dari seluruh komponen rubrik mata kuliah ini.
     */
    public function totalRubrikWeight(): int
    {
        return (int) $this->rubrikKomponens()->sum('bobot_persen');
    }
}
