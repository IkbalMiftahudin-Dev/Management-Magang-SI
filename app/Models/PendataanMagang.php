<?php

namespace App\Models;

use App\Enums\JenisProgramMagang;
use App\Enums\ModeKegiatan;
use App\Enums\StatusPendataan;
use Database\Factories\PendataanMagangFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'mahasiswa_id',
    'dosen_pembimbing_id',
    'dosen_kunjung_id',
    'semester_akademik',
    'nama_instansi',
    'posisi',
    'alamat_instansi',
    'kontak_instansi',
    'jenis_program',
    'mode_kegiatan',
    'tanggal_mulai',
    'tanggal_selesai',
    'loa_path',
    'loa_uploaded_at',
    'current_step',
    'pernyataan_disetujui',
    'status_pendataan',
    'catatan_verifikasi',
    'submitted_at',
    'verified_at',
    'verified_by',
])]
class PendataanMagang extends Model
{
    /** @use HasFactory<PendataanMagangFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis_program' => JenisProgramMagang::class,
            'mode_kegiatan' => ModeKegiatan::class,
            'status_pendataan' => StatusPendataan::class,
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'loa_uploaded_at' => 'datetime',
            'current_step' => 'integer',
            'pernyataan_disetujui' => 'boolean',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Mahasiswa, $this>
     */
    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    /**
     * @return BelongsTo<Dosen, $this>
     */
    public function dosenPembimbing(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_pembimbing_id');
    }

    /**
     * @return BelongsTo<Dosen, $this>
     */
    public function dosenKunjung(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_kunjung_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * @return HasMany<DokumenSk, $this>
     */
    public function dokumenSks(): HasMany
    {
        return $this->hasMany(DokumenSk::class);
    }

    /**
     * @return HasMany<Logbook, $this>
     */
    public function logbooks(): HasMany
    {
        return $this->hasMany(Logbook::class)->orderByDesc('tanggal');
    }

    /**
     * @return HasOne<BerkasAkhir, $this>
     */
    public function berkasAkhir(): HasOne
    {
        return $this->hasOne(BerkasAkhir::class);
    }

    /**
     * @return HasMany<KonversiMataKuliah, $this>
     */
    public function konversiMataKuliahs(): HasMany
    {
        return $this->hasMany(KonversiMataKuliah::class);
    }

    /**
     * Calculate internship duration in rounded weeks when both dates are present.
     */
    public function durationInWeeks(): ?int
    {
        if ($this->tanggal_mulai === null || $this->tanggal_selesai === null) {
            return null;
        }

        if ($this->tanggal_selesai->lt($this->tanggal_mulai)) {
            return 0;
        }

        return (int) max(1, round($this->tanggal_mulai->diffInDays($this->tanggal_selesai) / 7));
    }

    /**
     * Sum of converted SKS across all mapped courses.
     */
    public function totalConvertedSks(): int
    {
        return (int) $this->konversiMataKuliahs()->sum('sks_konversi');
    }
}
