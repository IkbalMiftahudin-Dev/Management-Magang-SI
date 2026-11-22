<?php

namespace App\Models;

use App\Enums\StatusBerkasAkhir;
use Database\Factories\BerkasAkhirFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pendataan_magang_id',
    'loa_path',
    'loa_uploaded_at',
    'rubrik_konversi_path',
    'rubrik_konversi_uploaded_at',
    'laporan_akhir_path',
    'laporan_akhir_uploaded_at',
    'draf_pks_path',
    'draf_pks_uploaded_at',
    'transkrip_instansi_path',
    'transkrip_instansi_uploaded_at',
    'sertifikat_path',
    'sertifikat_uploaded_at',
    'status_kelengkapan',
    'submitted_at',
])]
class BerkasAkhir extends Model
{
    /** @use HasFactory<BerkasAkhirFactory> */
    use HasFactory;

    /**
     * The 5 mandatory document path columns required before One-Stop Submission can be finalized.
     *
     * @var list<string>
     */
    public const MANDATORY_SLOTS = [
        'loa_path',
        'rubrik_konversi_path',
        'laporan_akhir_path',
        'draf_pks_path',
        'transkrip_instansi_path',
    ];

    /**
     * All 6 document path columns including the optional certificate slot.
     *
     * @var list<string>
     */
    public const ALL_SLOTS = [
        'loa_path',
        'rubrik_konversi_path',
        'laporan_akhir_path',
        'draf_pks_path',
        'transkrip_instansi_path',
        'sertifikat_path',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_kelengkapan' => StatusBerkasAkhir::class,
            'loa_uploaded_at' => 'datetime',
            'rubrik_konversi_uploaded_at' => 'datetime',
            'laporan_akhir_uploaded_at' => 'datetime',
            'draf_pks_uploaded_at' => 'datetime',
            'transkrip_instansi_uploaded_at' => 'datetime',
            'sertifikat_uploaded_at' => 'datetime',
            'submitted_at' => 'datetime',
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
     * Number of uploaded mandatory documents (0..5).
     */
    public function mandatoryUploadedCount(): int
    {
        $count = 0;

        foreach (self::MANDATORY_SLOTS as $column) {
            if (is_string($this->{$column}) && trim($this->{$column}) !== '') {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Number of uploaded documents across all 6 UI slots (0..6).
     */
    public function totalUploadedCount(): int
    {
        $count = 0;

        foreach (self::ALL_SLOTS as $column) {
            if (is_string($this->{$column}) && trim($this->{$column}) !== '') {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Progress percentage across the 6 UI slots (matches "X dari 6 lengkap").
     */
    public function completionPercentage(): int
    {
        return (int) round(($this->totalUploadedCount() / count(self::ALL_SLOTS)) * 100);
    }

    /**
     * Whether all 5 mandatory final documents have been uploaded.
     */
    public function isMandatoryComplete(): bool
    {
        return $this->mandatoryUploadedCount() === count(self::MANDATORY_SLOTS);
    }
}
