<?php

namespace App\Services;

use App\Enums\StatusBerkasAkhir;
use App\Models\BerkasAkhir;
use App\Models\PendataanMagang;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BerkasAkhirService
{
    /**
     * Map of valid One-Stop Submission slot keys to [pathColumn, timestampColumn, label, isMandatory].
     *
     * @var array<string, array{path: string, timestamp: string, label: string, mandatory: bool}>
     */
    public const SLOTS = [
        'loa' => [
            'path' => 'loa_path',
            'timestamp' => 'loa_uploaded_at',
            'label' => 'Letter of Acceptance',
            'mandatory' => true,
        ],
        'rubrik_konversi' => [
            'path' => 'rubrik_konversi_path',
            'timestamp' => 'rubrik_konversi_uploaded_at',
            'label' => 'Rubrik Konversi MK',
            'mandatory' => true,
        ],
        'laporan_akhir' => [
            'path' => 'laporan_akhir_path',
            'timestamp' => 'laporan_akhir_uploaded_at',
            'label' => 'Laporan Akhir Magang',
            'mandatory' => true,
        ],
        'draf_pks' => [
            'path' => 'draf_pks_path',
            'timestamp' => 'draf_pks_uploaded_at',
            'label' => 'Draf PKS Instansi',
            'mandatory' => true,
        ],
        'transkrip_instansi' => [
            'path' => 'transkrip_instansi_path',
            'timestamp' => 'transkrip_instansi_uploaded_at',
            'label' => 'Transkrip Nilai Instansi',
            'mandatory' => true,
        ],
        'sertifikat' => [
            'path' => 'sertifikat_path',
            'timestamp' => 'sertifikat_uploaded_at',
            'label' => 'Sertifikat Kegiatan',
            'mandatory' => false,
        ],
    ];

    public function __construct(
        private readonly PrivateFileService $fileService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Retrieve or initialize the student's BerkasAkhir record, syncing LOA from Pendataan Step 2 if present.
     */
    public function getOrCreateForPendataan(PendataanMagang $pendataan): BerkasAkhir
    {
        $berkas = BerkasAkhir::query()->firstOrCreate(
            ['pendataan_magang_id' => $pendataan->id],
            [
                'loa_path' => $pendataan->loa_path,
                'loa_uploaded_at' => $pendataan->loa_uploaded_at,
                'status_kelengkapan' => StatusBerkasAkhir::BelumLengkap,
            ],
        );

        if (($berkas->loa_path === null || $berkas->loa_path === '') && $pendataan->loa_path !== null) {
            $berkas->forceFill([
                'loa_path' => $pendataan->loa_path,
                'loa_uploaded_at' => $pendataan->loa_uploaded_at ?? now(),
            ])->save();
        }

        return $berkas;
    }

    /**
     * Upload or replace one of the 6 One-Stop Submission PDF slots inside private storage.
     */
    public function uploadSlot(
        User $actor,
        PendataanMagang $pendataan,
        string $slot,
        UploadedFile $file,
        ?Request $request = null,
    ): BerkasAkhir {
        if (! array_key_exists($slot, self::SLOTS)) {
            throw ValidationException::withMessages([
                'slot' => ['Slot dokumen berkas akhir tidak valid.'],
            ]);
        }

        $slotMeta = self::SLOTS[$slot];
        $berkas = $this->getOrCreateForPendataan($pendataan);

        return DB::transaction(function () use ($actor, $pendataan, $berkas, $slot, $slotMeta, $file, $request): BerkasAkhir {
            $oldPath = $berkas->{$slotMeta['path']};
            $stored = $this->fileService->storeUploadedFile($file, 'berkas-akhir/'.$pendataan->id);
            $uploadedAt = now();

            $berkas->forceFill([
                $slotMeta['path'] => $stored['path'],
                $slotMeta['timestamp'] => $uploadedAt,
            ])->save();

            if ($slot === 'loa') {
                $pendataan->forceFill([
                    'loa_path' => $stored['path'],
                    'loa_uploaded_at' => $uploadedAt,
                ])->save();
            }

            if ($oldPath !== null && $oldPath !== $stored['path']) {
                $this->fileService->deleteIfExists($oldPath);
            }

            $this->auditLogger->record(
                action: 'berkas_akhir.uploaded',
                user: $actor,
                request: $request,
                properties: [
                    'pendataan_magang_id' => $pendataan->id,
                    'slot' => $slot,
                    'total_uploaded' => $berkas->totalUploadedCount(),
                    'mandatory_uploaded' => $berkas->mandatoryUploadedCount(),
                ],
                subject: $berkas,
            );

            return $berkas->fresh();
        });
    }

    /**
     * Finalize One-Stop Submission once all 5 mandatory documents are present (Sertifikat slot 06 is optional).
     */
    public function submitAll(User $actor, PendataanMagang $pendataan, ?Request $request = null): BerkasAkhir
    {
        $berkas = $this->getOrCreateForPendataan($pendataan);

        $missingLabels = [];

        foreach (self::SLOTS as $meta) {
            if ($meta['mandatory'] && (! is_string($berkas->{$meta['path']}) || trim((string) $berkas->{$meta['path']}) === '')) {
                $missingLabels[] = $meta['label'];
            }
        }

        if ($missingLabels !== []) {
            throw ValidationException::withMessages([
                'berkas' => ['Dokumen wajib belum lengkap: '.implode(', ', $missingLabels).'.'],
            ]);
        }

        return DB::transaction(function () use ($actor, $pendataan, $berkas, $request): BerkasAkhir {
            $berkas->forceFill([
                'status_kelengkapan' => StatusBerkasAkhir::SiapDinilai,
                'submitted_at' => now(),
            ])->save();

            $this->auditLogger->record(
                action: 'berkas_akhir.submitted',
                user: $actor,
                request: $request,
                properties: [
                    'pendataan_magang_id' => $pendataan->id,
                    'total_uploaded' => $berkas->totalUploadedCount(),
                    'completion_percentage' => $berkas->completionPercentage(),
                ],
                subject: $berkas,
            );

            return $berkas->fresh();
        });
    }
}
