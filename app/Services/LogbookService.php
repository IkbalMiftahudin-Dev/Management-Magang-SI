<?php

namespace App\Services;

use App\Enums\StatusValidasiLogbook;
use App\Models\Dosen;
use App\Models\Logbook;
use App\Models\PendataanMagang;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class LogbookService
{
    public function __construct(
        private readonly PrivateFileService $fileService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * List all logbook entries for an internship ordered chronologically (newest first) for the 3-column table.
     *
     * @return Collection<int, Logbook>
     */
    public function listForPendataan(PendataanMagang $pendataan): Collection
    {
        return Logbook::query()
            ->with('validator')
            ->where('pendataan_magang_id', $pendataan->id)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Create a new weekly or monthly-recap logbook entry with optional photo evidence stored privately.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(
        User $actor,
        PendataanMagang $pendataan,
        array $data,
        ?UploadedFile $fotoBukti = null,
        ?Request $request = null,
    ): Logbook {
        $fotoPath = null;

        if ($fotoBukti !== null) {
            $fotoPath = $this->fileService->storeUploadedFile($fotoBukti, 'logbooks/'.$pendataan->id)['path'];
        }

        $logbook = Logbook::query()->create([
            'pendataan_magang_id' => $pendataan->id,
            'tipe_pengisian' => $data['tipe_pengisian'],
            'tanggal' => $data['tanggal'],
            'minggu_ke' => $data['minggu_ke'],
            'deskripsi_kegiatan' => $data['deskripsi_kegiatan'],
            'foto_bukti_path' => $fotoPath,
            'status_validasi' => StatusValidasiLogbook::Menunggu,
        ]);

        $this->auditLogger->record(
            action: 'logbook.created',
            user: $actor,
            request: $request,
            properties: [
                'pendataan_magang_id' => $pendataan->id,
                'minggu_ke' => $logbook->minggu_ke,
                'tipe_pengisian' => $logbook->tipe_pengisian->value,
            ],
            subject: $logbook,
        );

        return $logbook;
    }

    /**
     * Update an unvalidated logbook entry and replace photo evidence safely when provided.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(
        User $actor,
        Logbook $logbook,
        array $data,
        ?UploadedFile $fotoBukti = null,
        ?Request $request = null,
    ): Logbook {
        $oldPath = $logbook->foto_bukti_path;
        $newPath = $oldPath;

        if ($fotoBukti !== null) {
            $newPath = $this->fileService->storeUploadedFile($fotoBukti, 'logbooks/'.$logbook->pendataan_magang_id)['path'];
        }

        $logbook->fill([
            'tipe_pengisian' => $data['tipe_pengisian'] ?? $logbook->tipe_pengisian,
            'tanggal' => $data['tanggal'] ?? $logbook->tanggal,
            'minggu_ke' => $data['minggu_ke'] ?? $logbook->minggu_ke,
            'deskripsi_kegiatan' => $data['deskripsi_kegiatan'] ?? $logbook->deskripsi_kegiatan,
            'foto_bukti_path' => $newPath,
            'status_validasi' => StatusValidasiLogbook::Menunggu,
        ]);

        $logbook->save();

        if ($fotoBukti !== null && $oldPath !== null && $oldPath !== $newPath) {
            $this->fileService->deleteIfExists($oldPath);
        }

        $this->auditLogger->record(
            action: 'logbook.updated',
            user: $actor,
            request: $request,
            properties: ['minggu_ke' => $logbook->minggu_ke],
            subject: $logbook,
        );

        return $logbook->fresh(['validator']);
    }

    /**
     * Delete an unvalidated logbook entry along with its private photo file.
     */
    public function delete(User $actor, Logbook $logbook, ?Request $request = null): void
    {
        $path = $logbook->foto_bukti_path;
        $snapshot = [
            'id' => $logbook->id,
            'pendataan_magang_id' => $logbook->pendataan_magang_id,
            'minggu_ke' => $logbook->minggu_ke,
        ];

        $logbook->delete();
        $this->fileService->deleteIfExists($path);

        $this->auditLogger->record(
            action: 'logbook.deleted',
            user: $actor,
            request: $request,
            properties: $snapshot,
        );
    }

    /**
     * Validate or request revision on a student's logbook entry (exclusive to the assigned Dosen Pembimbing UPI).
     */
    public function validateByDosen(
        User $actor,
        Dosen $dosen,
        Logbook $logbook,
        StatusValidasiLogbook $status,
        ?string $catatan = null,
        ?Request $request = null,
    ): Logbook {
        $logbook->forceFill([
            'status_validasi' => $status,
            'catatan_dosen' => $catatan,
            'validated_by' => $dosen->id,
            'validated_at' => now(),
        ])->save();

        $this->auditLogger->record(
            action: 'logbook.validated',
            user: $actor,
            request: $request,
            properties: [
                'pendataan_magang_id' => $logbook->pendataan_magang_id,
                'status_validasi' => $status->value,
            ],
            subject: $logbook,
        );

        return $logbook->fresh(['validator']);
    }
}
