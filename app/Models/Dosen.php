<?php

namespace App\Models;

use Database\Factories\DosenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'nip',
    'nama',
    'email',
    'bidang_kepakaran',
])]
class Dosen extends Model
{
    /** @use HasFactory<DosenFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mata kuliah konversi yang diampu oleh dosen ini.
     *
     * @return HasMany<MataKuliah, $this>
     */
    public function mataKuliahs(): HasMany
    {
        return $this->hasMany(MataKuliah::class, 'dosen_pengampu_id');
    }

    /**
     * Lowongan magang dengan dosen ini sebagai penanggung jawab.
     *
     * @return HasMany<LowonganMagang, $this>
     */
    public function lowonganMagangs(): HasMany
    {
        return $this->hasMany(LowonganMagang::class, 'dosen_penanggung_jawab_id');
    }

    /**
     * Mahasiswa bimbingan UPI pada pendataan magang.
     *
     * @return HasMany<PendataanMagang, $this>
     */
    public function pendataanBimbingans(): HasMany
    {
        return $this->hasMany(PendataanMagang::class, 'dosen_pembimbing_id');
    }

    /**
     * Penugasan dosen ini sebagai pembimbing lapangan / dosen kunjung.
     *
     * @return HasMany<PendataanMagang, $this>
     */
    public function pendataanKunjungs(): HasMany
    {
        return $this->hasMany(PendataanMagang::class, 'dosen_kunjung_id');
    }

    /**
     * Konversi mata kuliah yang menjadi tanggung jawab penilaian dosen ini.
     *
     * @return HasMany<KonversiMataKuliah, $this>
     */
    public function konversiDinilai(): HasMany
    {
        return $this->hasMany(KonversiMataKuliah::class, 'dosen_penilai_id');
    }
}
