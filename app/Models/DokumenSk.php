<?php

namespace App\Models;

use App\Enums\JenisSk;
use App\Enums\StatusPengesahanSk;
use Database\Factories\DokumenSkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pendataan_magang_id',
    'jenis_sk',
    'nomor_sk',
    'file_path',
    'checksum_sha256',
    'status_pengesahan',
    'tanggal_diterbitkan',
    'generated_by',
    'approved_by',
    'approved_at',
])]
class DokumenSk extends Model
{
    /** @use HasFactory<DokumenSkFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis_sk' => JenisSk::class,
            'status_pengesahan' => StatusPengesahanSk::class,
            'tanggal_diterbitkan' => 'date',
            'approved_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
