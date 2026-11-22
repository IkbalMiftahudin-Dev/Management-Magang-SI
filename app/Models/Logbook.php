<?php

namespace App\Models;

use App\Enums\StatusValidasiLogbook;
use App\Enums\TipeLogbook;
use Database\Factories\LogbookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pendataan_magang_id',
    'tipe_pengisian',
    'tanggal',
    'minggu_ke',
    'deskripsi_kegiatan',
    'foto_bukti_path',
    'status_validasi',
    'catatan_dosen',
    'validated_by',
    'validated_at',
])]
class Logbook extends Model
{
    /** @use HasFactory<LogbookFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipe_pengisian' => TipeLogbook::class,
            'status_validasi' => StatusValidasiLogbook::class,
            'tanggal' => 'date',
            'minggu_ke' => 'integer',
            'validated_at' => 'datetime',
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
     * @return BelongsTo<Dosen, $this>
     */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'validated_by');
    }
}
