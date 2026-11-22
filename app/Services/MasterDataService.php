<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\RubrikKomponen;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MasterDataService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Create or update a conversion course together with its weighted rubric components (must sum to 100%).
     *
     * @param  array<string, mixed>  $courseData
     * @param  list<array{nama_komponen: string, deskripsi?: ?string, bobot_persen: int|string}>  $rubrikItems
     */
    public function saveMataKuliahWithRubrik(
        User $actor,
        array $courseData,
        array $rubrikItems,
        ?MataKuliah $existing = null,
        ?Request $request = null,
    ): MataKuliah {
        $totalWeight = 0;

        foreach ($rubrikItems as $item) {
            $totalWeight += (int) $item['bobot_persen'];
        }

        if ($totalWeight !== 100) {
            throw ValidationException::withMessages([
                'rubrik_komponens' => [sprintf('Total bobot seluruh komponen rubrik harus tepat 100%% (saat ini %d%%).', $totalWeight)],
            ]);
        }

        return DB::transaction(function () use ($actor, $courseData, $rubrikItems, $existing, $request): MataKuliah {
            $isNew = $existing === null;
            $mataKuliah = $existing ?? new MataKuliah;

            $mataKuliah->fill([
                'kode_mk' => strtoupper(trim((string) $courseData['kode_mk'])),
                'nama_mk' => $courseData['nama_mk'],
                'sks' => (int) $courseData['sks'],
                'dosen_pengampu_id' => $courseData['dosen_pengampu_id'] ?? null,
                'deskripsi' => $courseData['deskripsi'] ?? null,
                'is_active' => (bool) ($courseData['is_active'] ?? true),
            ]);
            $mataKuliah->save();

            // Replace/update components cleanly while avoiding deletion of components already referenced in historical grades.
            $keptIds = [];

            foreach (array_values($rubrikItems) as $index => $item) {
                $urutan = $index + 1;
                $komponen = RubrikKomponen::query()->updateOrCreate(
                    [
                        'mata_kuliah_id' => $mataKuliah->id,
                        'urutan' => $urutan,
                    ],
                    [
                        'nama_komponen' => $item['nama_komponen'],
                        'deskripsi' => $item['deskripsi'] ?? null,
                        'bobot_persen' => (int) $item['bobot_persen'],
                    ],
                );
                $keptIds[] = $komponen->id;
            }

            RubrikKomponen::query()
                ->where('mata_kuliah_id', $mataKuliah->id)
                ->whereNotIn('id', $keptIds)
                ->doesntHave('penilaianDetails')
                ->delete();

            $this->auditLogger->record(
                action: $isNew ? 'mata_kuliah.created' : 'mata_kuliah.updated',
                user: $actor,
                request: $request,
                properties: [
                    'kode_mk' => $mataKuliah->kode_mk,
                    'nama_mk' => $mataKuliah->nama_mk,
                    'sks' => $mataKuliah->sks,
                    'komponen_count' => count($rubrikItems),
                ],
                subject: $mataKuliah,
            );

            return $mataKuliah->fresh(['dosenPengampu', 'rubrikKomponens']);
        });
    }

    /**
     * Create or update a lecturer reference record.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveDosen(User $actor, array $data, ?Dosen $existing = null, ?Request $request = null): Dosen
    {
        $isNew = $existing === null;
        $dosen = $existing ?? new Dosen;
        $dosen->fill($data);
        $dosen->save();

        $this->auditLogger->record(
            action: $isNew ? 'dosen.created' : 'dosen.updated',
            user: $actor,
            request: $request,
            properties: ['nip' => $dosen->nip, 'nama' => $dosen->nama],
            subject: $dosen,
        );

        return $dosen;
    }

    /**
     * Create or update a student reference record.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveMahasiswa(User $actor, array $data, ?Mahasiswa $existing = null, ?Request $request = null): Mahasiswa
    {
        $isNew = $existing === null;
        $mahasiswa = $existing ?? new Mahasiswa;
        $mahasiswa->fill($data);
        $mahasiswa->save();

        $this->auditLogger->record(
            action: $isNew ? 'mahasiswa.created' : 'mahasiswa.updated',
            user: $actor,
            request: $request,
            properties: ['nim' => $mahasiswa->nim, 'nama' => $mahasiswa->nama],
            subject: $mahasiswa,
        );

        return $mahasiswa;
    }

    /**
     * Filter system audit logs for Admin monitoring.
     *
     * @return Collection<int, ActivityLog>
     */
    public function listAuditLogs(?string $action = null, ?string $role = null, ?string $search = null, int $limit = 100): Collection
    {
        $query = ActivityLog::query()->with('user')->latest('created_at')->latest('id');

        if ($action !== null && trim($action) !== '') {
            $query->where('action', 'like', '%'.trim($action).'%');
        }

        if ($role !== null && trim($role) !== '') {
            $query->where('role', trim($role));
        }

        if ($search !== null && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(static function (Builder $builder) use ($term): void {
                $builder->where('action', 'like', $term)
                    ->orWhere('ip_address', 'like', $term)
                    ->orWhereHas('user', static function (Builder $user) use ($term): void {
                        $user->where('name', 'like', $term)
                            ->orWhere('email', 'like', $term);
                    });
            });
        }

        return $query->limit(min(max($limit, 1), 200))->get();
    }
}
