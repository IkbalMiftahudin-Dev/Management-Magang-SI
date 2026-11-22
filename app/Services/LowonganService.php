<?php

namespace App\Services;

use App\Enums\StatusLowongan;
use App\Enums\SumberLowongan;
use App\Models\LowonganMagang;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class LowonganService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Query active internship vacancies for the student portal (with search and category filter).
     *
     * @return Collection<int, LowonganMagang>
     */
    public function listForMahasiswa(?string $search = null, ?string $sumber = null): Collection
    {
        return $this->baseQuery($search, $sumber)
            ->where('status', StatusLowongan::Aktif)
            ->orderByDesc('tanggal_posting')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Query all vacancies for Staff Prodi, Kaprodi, and Admin management screens.
     *
     * @return Collection<int, LowonganMagang>
     */
    public function listForManagement(?string $search = null, ?string $sumber = null, ?string $status = null): Collection
    {
        $query = $this->baseQuery($search, $sumber);

        if ($status !== null && StatusLowongan::tryFrom($status) !== null) {
            $query->where('status', $status);
        }

        return $query
            ->orderByDesc('tanggal_posting')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Compute the 4 KPI cards displayed on the Kelola Lowongan management page.
     *
     * @return array{aktif: int, draft: int, total_pelamar: int, mitra_terhubung: int}
     */
    public function managementMetrics(): array
    {
        return [
            'aktif' => LowonganMagang::query()->where('status', StatusLowongan::Aktif)->count(),
            'draft' => LowonganMagang::query()->where('status', StatusLowongan::Draft)->count(),
            'total_pelamar' => (int) LowonganMagang::query()->sum('jumlah_pelamar'),
            'mitra_terhubung' => LowonganMagang::query()
                ->where('status', StatusLowongan::Aktif)
                ->distinct('nama_instansi')
                ->count('nama_instansi'),
        ];
    }

    /**
     * Create a new vacancy and record an audit log entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data, ?Request $request = null): LowonganMagang
    {
        $lowongan = LowonganMagang::query()->create([
            'judul_lowongan' => $data['judul_lowongan'],
            'nama_instansi' => $data['nama_instansi'],
            'posisi_bidang' => $data['posisi_bidang'],
            'lokasi_mode' => $data['lokasi_mode'],
            'sumber_lowongan' => $data['sumber_lowongan'],
            'kontak' => $data['kontak'],
            'deskripsi' => $data['deskripsi'],
            'persyaratan' => $data['persyaratan'] ?? null,
            'tanggal_posting' => $data['tanggal_posting'] ?? now()->toDateString(),
            'batas_waktu' => $data['batas_waktu'] ?? null,
            'status' => $data['status'] ?? StatusLowongan::Aktif->value,
            'dosen_penanggung_jawab_id' => $data['dosen_penanggung_jawab_id'] ?? null,
            'created_by' => $actor->id,
        ]);

        $this->auditLogger->record(
            action: 'lowongan.created',
            user: $actor,
            request: $request,
            properties: [
                'judul_lowongan' => $lowongan->judul_lowongan,
                'nama_instansi' => $lowongan->nama_instansi,
                'sumber_lowongan' => $lowongan->sumber_lowongan->value,
                'status' => $lowongan->status->value,
            ],
            subject: $lowongan,
        );

        return $lowongan->load('dosenPenanggungJawab');
    }

    /**
     * Update an existing vacancy and record an audit log entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, LowonganMagang $lowongan, array $data, ?Request $request = null): LowonganMagang
    {
        $lowongan->fill($data);
        $lowongan->save();

        $this->auditLogger->record(
            action: 'lowongan.updated',
            user: $actor,
            request: $request,
            properties: [
                'judul_lowongan' => $lowongan->judul_lowongan,
                'status' => $lowongan->status->value,
            ],
            subject: $lowongan,
        );

        return $lowongan->fresh(['dosenPenanggungJawab']);
    }

    /**
     * Delete a vacancy and record an audit log entry.
     */
    public function delete(User $actor, LowonganMagang $lowongan, ?Request $request = null): void
    {
        $snapshot = [
            'id' => $lowongan->id,
            'judul_lowongan' => $lowongan->judul_lowongan,
            'nama_instansi' => $lowongan->nama_instansi,
        ];

        $lowongan->delete();

        $this->auditLogger->record(
            action: 'lowongan.deleted',
            user: $actor,
            request: $request,
            properties: $snapshot,
        );
    }

    /**
     * @return Builder<LowonganMagang>
     */
    private function baseQuery(?string $search, ?string $sumber): Builder
    {
        $query = LowonganMagang::query()->with('dosenPenanggungJawab');

        if ($search !== null && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(static function (Builder $builder) use ($term): void {
                $builder->where('judul_lowongan', 'like', $term)
                    ->orWhere('nama_instansi', 'like', $term)
                    ->orWhere('posisi_bidang', 'like', $term)
                    ->orWhere('lokasi_mode', 'like', $term);
            });
        }

        if ($sumber !== null && SumberLowongan::tryFrom($sumber) !== null) {
            $query->where('sumber_lowongan', $sumber);
        }

        return $query;
    }
}
