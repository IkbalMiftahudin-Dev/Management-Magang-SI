<?php

namespace App\Models;

use App\Enums\StatusLowongan;
use App\Enums\SumberLowongan;
use Database\Factories\LowonganMagangFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'judul_lowongan',
    'nama_instansi',
    'posisi_bidang',
    'lokasi_mode',
    'sumber_lowongan',
    'kontak',
    'deskripsi',
    'persyaratan',
    'logo_path',
    'jumlah_pelamar',
    'tanggal_posting',
    'batas_waktu',
    'status',
    'dosen_penanggung_jawab_id',
    'created_by',
])]
class LowonganMagang extends Model
{
    /** @use HasFactory<LowonganMagangFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sumber_lowongan' => SumberLowongan::class,
            'status' => StatusLowongan::class,
            'jumlah_pelamar' => 'integer',
            'tanggal_posting' => 'date',
            'batas_waktu' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Dosen, $this>
     */
    public function dosenPenanggungJawab(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_penanggung_jawab_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
