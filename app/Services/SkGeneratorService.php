<?php

namespace App\Services;

use App\Enums\JenisSk;
use App\Enums\StatusPendataan;
use App\Enums\StatusPengesahanSk;
use App\Models\DokumenSk;
use App\Models\PendataanMagang;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SkGeneratorService
{
    public function __construct(
        private readonly PrivateFileService $fileService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Generate (or regenerate while still draft) an automated SK PDF for a verified internship.
     */
    public function generate(
        User $actor,
        PendataanMagang $pendataan,
        JenisSk $jenisSk,
        ?Request $request = null,
    ): DokumenSk {
        if (! in_array($pendataan->status_pendataan, [StatusPendataan::Terverifikasi, StatusPendataan::Selesai], true)) {
            throw ValidationException::withMessages([
                'pendataan' => ['Draf SK hanya dapat dibuat untuk pendataan yang sudah berstatus Terverifikasi.'],
            ]);
        }

        if ($jenisSk === JenisSk::SkKunjung && $pendataan->dosen_kunjung_id === null) {
            throw ValidationException::withMessages([
                'dosen_kunjung_id' => ['Pembimbing Lapangan / Dosen Kunjung wajib ditetapkan sebelum membuat SK Kunjung.'],
            ]);
        }

        $pendataan->load([
            'mahasiswa',
            'dosenPembimbing',
            'dosenKunjung',
            'konversiMataKuliahs.mataKuliah.dosenPengampu',
        ]);

        return DB::transaction(function () use ($actor, $pendataan, $jenisSk, $request): DokumenSk {
            $existing = DokumenSk::query()
                ->where('pendataan_magang_id', $pendataan->id)
                ->where('jenis_sk', $jenisSk)
                ->lockForUpdate()
                ->first();

            if ($existing !== null && $existing->status_pengesahan === StatusPengesahanSk::Disahkan) {
                throw ValidationException::withMessages([
                    'jenis_sk' => ['SK yang sudah disahkan oleh Kaprodi tidak dapat ditimpa kembali.'],
                ]);
            }

            $nomorSk = $existing?->nomor_sk ?? $this->generateUniqueSkNumber($jenisSk);
            $tanggalTerbit = now()->toDateString();

            $pdfBinary = $this->renderPdfBytes(
                pendataan: $pendataan,
                jenisSk: $jenisSk,
                nomorSk: $nomorSk,
                statusPengesahan: StatusPengesahanSk::Draft,
                tanggalTerbit: $tanggalTerbit,
                approver: null,
                approvedAt: null,
            );

            $stored = $this->fileService->storeRaw($pdfBinary, 'sk', 'pdf');
            $oldPath = $existing?->file_path;

            $dokumenSk = DokumenSk::query()->updateOrCreate(
                [
                    'pendataan_magang_id' => $pendataan->id,
                    'jenis_sk' => $jenisSk,
                ],
                [
                    'nomor_sk' => $nomorSk,
                    'file_path' => $stored['path'],
                    'checksum_sha256' => $stored['checksum'],
                    'status_pengesahan' => StatusPengesahanSk::Draft,
                    'tanggal_diterbitkan' => $tanggalTerbit,
                    'generated_by' => $actor->id,
                ],
            );

            if ($oldPath !== null && $oldPath !== $stored['path']) {
                $this->fileService->deleteIfExists($oldPath);
            }

            $this->auditLogger->record(
                action: 'sk.generated',
                user: $actor,
                request: $request,
                properties: [
                    'pendataan_magang_id' => $pendataan->id,
                    'jenis_sk' => $jenisSk->value,
                    'nomor_sk' => $dokumenSk->nomor_sk,
                    'checksum_sha256' => $dokumenSk->checksum_sha256,
                ],
                subject: $dokumenSk,
            );

            return $dokumenSk->fresh(['pendataanMagang.mahasiswa', 'generator', 'approver']);
        });
    }

    /**
     * Bulk-generate missing draft SK documents for all verified pendataan records (Otomatisasi Generasi SK button).
     *
     * @return array{generated: int, skipped: int}
     */
    public function generateBulkMissingDrafts(User $actor, ?Request $request = null): array
    {
        $verifiedList = PendataanMagang::query()
            ->with('dokumenSks')
            ->whereIn('status_pendataan', [StatusPendataan::Terverifikasi, StatusPendataan::Selesai])
            ->get();

        $generated = 0;
        $skipped = 0;

        foreach ($verifiedList as $pendataan) {
            $hasSkMagang = $pendataan->dokumenSks->contains('jenis_sk', JenisSk::SkMagang);

            if (! $hasSkMagang) {
                $this->generate($actor, $pendataan, JenisSk::SkMagang, $request);
                $generated++;
            } else {
                $skipped++;
            }

            if ($pendataan->dosen_kunjung_id !== null) {
                $hasSkKunjung = $pendataan->dokumenSks->contains('jenis_sk', JenisSk::SkKunjung);

                if (! $hasSkKunjung) {
                    $this->generate($actor, $pendataan, JenisSk::SkKunjung, $request);
                    $generated++;
                }
            }
        }

        return [
            'generated' => $generated,
            'skipped' => $skipped,
        ];
    }

    /**
     * Officially approve a draft SK (exclusive to Kaprodi), re-rendering the final signed PDF with SHA-256 checksum.
     */
    public function approve(User $kaprodi, DokumenSk $dokumenSk, ?Request $request = null): DokumenSk
    {
        if ($dokumenSk->status_pengesahan === StatusPengesahanSk::Disahkan) {
            throw ValidationException::withMessages([
                'dokumen_sk' => ['Dokumen SK ini sudah berstatus Disahkan.'],
            ]);
        }

        $pendataan = $dokumenSk->pendataanMagang()->with([
            'mahasiswa',
            'dosenPembimbing',
            'dosenKunjung',
            'konversiMataKuliahs.mataKuliah.dosenPengampu',
        ])->firstOrFail();

        return DB::transaction(function () use ($kaprodi, $dokumenSk, $pendataan, $request): DokumenSk {
            $approvedAt = now();
            $tanggalTerbit = $approvedAt->toDateString();

            $pdfBinary = $this->renderPdfBytes(
                pendataan: $pendataan,
                jenisSk: $dokumenSk->jenis_sk,
                nomorSk: $dokumenSk->nomor_sk,
                statusPengesahan: StatusPengesahanSk::Disahkan,
                tanggalTerbit: $tanggalTerbit,
                approver: $kaprodi,
                approvedAt: $approvedAt->format('d M Y H:i').' WIB',
            );

            $stored = $this->fileService->storeRaw($pdfBinary, 'sk', 'pdf');
            $oldPath = $dokumenSk->file_path;

            $dokumenSk->forceFill([
                'file_path' => $stored['path'],
                'checksum_sha256' => $stored['checksum'],
                'status_pengesahan' => StatusPengesahanSk::Disahkan,
                'tanggal_diterbitkan' => $tanggalTerbit,
                'approved_by' => $kaprodi->id,
                'approved_at' => $approvedAt,
            ])->save();

            if ($oldPath !== '' && $oldPath !== $stored['path']) {
                $this->fileService->deleteIfExists($oldPath);
            }

            $this->auditLogger->record(
                action: 'sk.approved',
                user: $kaprodi,
                request: $request,
                properties: [
                    'pendataan_magang_id' => $pendataan->id,
                    'jenis_sk' => $dokumenSk->jenis_sk->value,
                    'nomor_sk' => $dokumenSk->nomor_sk,
                    'checksum_sha256' => $dokumenSk->checksum_sha256,
                ],
                subject: $dokumenSk,
            );

            return $dokumenSk->fresh(['pendataanMagang.mahasiswa', 'generator', 'approver']);
        });
    }

    /**
     * Generate a collision-free sequential SK number for the given type and year.
     */
    private function generateUniqueSkNumber(JenisSk $jenisSk): string
    {
        $prefix = $jenisSk === JenisSk::SkMagang ? 'MG' : 'KL';
        $year = now()->year;
        $pattern = sprintf('SK/RPL/%s/%d/', $prefix, $year);

        $latest = DokumenSk::query()
            ->where('nomor_sk', 'like', $pattern.'%')
            ->orderByDesc('id')
            ->value('nomor_sk');

        $nextSeq = 1;

        if (is_string($latest)) {
            $suffix = (int) substr($latest, strlen($pattern));
            $nextSeq = $suffix + 1;
        }

        do {
            $candidate = sprintf('%s%04d', $pattern, $nextSeq);
            $nextSeq++;
        } while (DokumenSk::query()->where('nomor_sk', $candidate)->exists());

        return $candidate;
    }

    private function renderPdfBytes(
        PendataanMagang $pendataan,
        JenisSk $jenisSk,
        string $nomorSk,
        StatusPengesahanSk $statusPengesahan,
        string $tanggalTerbit,
        ?User $approver,
        ?string $approvedAt,
    ): string {
        return Pdf::loadView('pdf.sk-magang', [
            'pendataan' => $pendataan,
            'jenisSk' => $jenisSk,
            'nomorSk' => $nomorSk,
            'statusPengesahan' => $statusPengesahan,
            'tanggalTerbit' => $tanggalTerbit,
            'approver' => $approver,
            'approvedAt' => $approvedAt,
        ])->setPaper('a4', 'portrait')->output();
    }
}
