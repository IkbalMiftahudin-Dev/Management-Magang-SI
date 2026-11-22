<?php

namespace App\Services;

use App\Enums\StatusBerkasAkhir;
use App\Enums\StatusPendataan;
use App\Enums\StatusPengesahanSk;
use App\Models\DokumenSk;
use App\Models\PendataanMagang;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class VerifikasiProdiService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * List submitted internship registrations for the Staff Prodi & Kaprodi verification table.
     *
     * @return Collection<int, PendataanMagang>
     */
    public function listRekap(?string $search = null, ?string $status = null, ?string $semester = null): Collection
    {
        $query = PendataanMagang::query()
            ->with([
                'mahasiswa',
                'dosenPembimbing',
                'dosenKunjung',
                'konversiMataKuliahs.mataKuliah',
                'dokumenSks',
                'berkasAkhir',
            ])
            ->where('status_pendataan', '!=', StatusPendataan::Draft);

        if ($search !== null && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(static function (Builder $builder) use ($term): void {
                $builder->where('nama_instansi', 'like', $term)
                    ->orWhere('posisi', 'like', $term)
                    ->orWhereHas('mahasiswa', static function (Builder $mhs) use ($term): void {
                        $mhs->where('nama', 'like', $term)
                            ->orWhere('nim', 'like', $term);
                    });
            });
        }

        if ($status !== null && StatusPendataan::tryFrom($status) !== null) {
            $query->where('status_pendataan', $status);
        }

        if ($semester !== null && trim($semester) !== '') {
            $query->where('semester_akademik', trim($semester));
        }

        return $query->orderByDesc('submitted_at')->orderByDesc('id')->get();
    }

    /**
     * Compute the 4 KPI cards on the Verifikasi & Penilaian page.
     *
     * @return array{menunggu_verifikasi: int, pendataan_aktif: int, sk_belum_disahkan: int, kelengkapan_berkas_persen: int}
     */
    public function dashboardMetrics(): array
    {
        $menunggu = PendataanMagang::query()
            ->where('status_pendataan', StatusPendataan::MenungguVerifikasi)
            ->count();

        $aktif = PendataanMagang::query()
            ->whereIn('status_pendataan', [StatusPendataan::Terverifikasi, StatusPendataan::Selesai])
            ->count();

        $skBelumDisahkan = DokumenSk::query()
            ->where('status_pengesahan', StatusPengesahanSk::Draft)
            ->count();

        $berkasLengkap = PendataanMagang::query()
            ->whereIn('status_pendataan', [StatusPendataan::Terverifikasi, StatusPendataan::Selesai])
            ->whereHas('berkasAkhir', static function (Builder $query): void {
                $query->whereIn('status_kelengkapan', [
                    StatusBerkasAkhir::SiapDinilai,
                    StatusBerkasAkhir::Terverifikasi,
                ]);
            })
            ->count();

        $kelengkapanPersen = $aktif > 0 ? (int) round(($berkasLengkap / $aktif) * 100) : 0;

        return [
            'menunggu_verifikasi' => $menunggu,
            'pendataan_aktif' => $aktif,
            'sk_belum_disahkan' => $skBelumDisahkan,
            'kelengkapan_berkas_persen' => $kelengkapanPersen,
        ];
    }

    /**
     * Approve (Terverifikasi) or return for revision (Perlu Revisi) a student's pendataan.
     */
    public function updateStatus(
        User $actor,
        PendataanMagang $pendataan,
        StatusPendataan $targetStatus,
        ?string $catatan = null,
        ?Request $request = null,
    ): PendataanMagang {
        $previousStatus = $pendataan->status_pendataan->value;

        $pendataan->forceFill([
            'status_pendataan' => $targetStatus,
            'catatan_verifikasi' => $targetStatus === StatusPendataan::PerluRevisi ? $catatan : null,
            'verified_at' => $targetStatus === StatusPendataan::Terverifikasi ? now() : null,
            'verified_by' => $targetStatus === StatusPendataan::Terverifikasi ? $actor->id : null,
        ])->save();

        $action = $targetStatus === StatusPendataan::Terverifikasi
            ? 'pendataan.verified'
            : 'pendataan.revision_requested';

        $this->auditLogger->record(
            action: $action,
            user: $actor,
            request: $request,
            properties: [
                'previous_status' => $previousStatus,
                'new_status' => $targetStatus->value,
                'catatan_verifikasi' => $pendataan->catatan_verifikasi,
            ],
            subject: $pendataan,
        );

        return $pendataan->fresh(['mahasiswa', 'dosenPembimbing', 'dosenKunjung', 'dokumenSks']);
    }

    /**
     * Assign or update the field supervisor / visiting lecturer (Pembimbing Lapangan / Dosen Kunjung).
     */
    public function assignPembimbingLapangan(
        User $actor,
        PendataanMagang $pendataan,
        int $dosenKunjungId,
        ?Request $request = null,
    ): PendataanMagang {
        $previousDosenId = $pendataan->dosen_kunjung_id;

        $pendataan->forceFill([
            'dosen_kunjung_id' => $dosenKunjungId,
        ])->save();

        $this->auditLogger->record(
            action: 'pendataan.pembimbing_lapangan_assigned',
            user: $actor,
            request: $request,
            properties: [
                'previous_dosen_kunjung_id' => $previousDosenId,
                'dosen_kunjung_id' => $dosenKunjungId,
            ],
            subject: $pendataan,
        );

        return $pendataan->fresh(['mahasiswa', 'dosenPembimbing', 'dosenKunjung']);
    }
}
