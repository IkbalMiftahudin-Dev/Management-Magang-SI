<?php

namespace App\Services;

use App\Enums\StatusPendataan;
use App\Enums\StatusPenilaian;
use App\Models\Dosen;
use App\Models\KonversiMataKuliah;
use App\Models\MataKuliah;
use App\Models\PenilaianKonversiDetail;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenilaianKonversiService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Build the Penilaian & SK overview payload for a lecturer teaching one or more conversion courses.
     *
     * @return array{
     *     courses: Collection<int, MataKuliah>,
     *     selected_course: ?MataKuliah,
     *     metrics: array{pengajuan: int, belum_dinilai: int, draf: int, dipublikasikan: int},
     *     submissions: Collection<int, KonversiMataKuliah>
     * }
     */
    public function dashboardForDosen(Dosen $dosen, ?int $mataKuliahId = null, ?string $search = null): array
    {
        $courses = MataKuliah::query()
            ->where('dosen_pengampu_id', $dosen->id)
            ->where('is_active', true)
            ->orderBy('kode_mk')
            ->get();

        $selectedCourse = $mataKuliahId !== null
            ? $courses->firstWhere('id', $mataKuliahId)
            : $courses->first();

        $baseQuery = KonversiMataKuliah::query()
            ->with([
                'mataKuliah',
                'pendataanMagang.mahasiswa',
                'pendataanMagang.berkasAkhir',
            ])
            ->where('dosen_penilai_id', $dosen->id);

        if ($selectedCourse !== null) {
            $baseQuery->where('mata_kuliah_id', $selectedCourse->id);
        }

        $allForCourse = (clone $baseQuery)->get();

        $metrics = [
            'pengajuan' => $allForCourse->count(),
            'belum_dinilai' => $allForCourse->where('status_penilaian', StatusPenilaian::BelumDinilai)->count(),
            'draf' => $allForCourse->where('status_penilaian', StatusPenilaian::Draf)->count(),
            'dipublikasikan' => $allForCourse->where('status_penilaian', StatusPenilaian::Dipublikasikan)->count(),
        ];

        if ($search !== null && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $baseQuery->whereHas('pendataanMagang', static function (Builder $pendataan) use ($term): void {
                $pendataan->where('nama_instansi', 'like', $term)
                    ->orWhere('posisi', 'like', $term)
                    ->orWhereHas('mahasiswa', static function (Builder $mhs) use ($term): void {
                        $mhs->where('nama', 'like', $term)
                            ->orWhere('nim', 'like', $term);
                    });
            });
        }

        $submissions = $baseQuery->orderByDesc('id')->get();

        return [
            'courses' => $courses,
            'selected_course' => $selectedCourse,
            'metrics' => $metrics,
            'submissions' => $submissions,
        ];
    }

    /**
     * Save weighted rubric scores either as a mutable draft or as a locked published final grade.
     *
     * @param  list<array{rubrik_komponen_id: int, nilai: float|int|string}>  $scores
     */
    public function saveEvaluation(
        User $actor,
        KonversiMataKuliah $konversi,
        array $scores,
        ?float $nilaiInstansi,
        ?string $catatanDosen,
        bool $publish,
        ?Request $request = null,
    ): KonversiMataKuliah {
        if ($konversi->is_locked) {
            throw ValidationException::withMessages([
                'konversi' => ['Nilai konversi mata kuliah ini sudah dipublikasikan dan terkunci.'],
            ]);
        }

        $rubrikKomponens = $konversi->mataKuliah()
            ->with('rubrikKomponens')
            ->firstOrFail()
            ->rubrikKomponens
            ->keyBy('id');

        if ($rubrikKomponens->isEmpty()) {
            throw ValidationException::withMessages([
                'scores' => ['Mata kuliah ini belum memiliki komponen rubrik penilaian.'],
            ]);
        }

        $scoreMap = [];

        foreach ($scores as $entry) {
            $componentId = (int) $entry['rubrik_komponen_id'];

            if (! $rubrikKomponens->has($componentId)) {
                throw ValidationException::withMessages([
                    'scores' => ['Terdapat komponen rubrik yang bukan milik mata kuliah ini.'],
                ]);
            }

            $scoreMap[$componentId] = round((float) $entry['nilai'], 2);
        }

        if ($publish && count($scoreMap) !== $rubrikKomponens->count()) {
            throw ValidationException::withMessages([
                'scores' => ['Seluruh komponen rubrik wajib diisi sebelum mempublikasikan nilai.'],
            ]);
        }

        return DB::transaction(function () use (
            $actor,
            $konversi,
            $rubrikKomponens,
            $scoreMap,
            $nilaiInstansi,
            $catatanDosen,
            $publish,
            $request,
        ): KonversiMataKuliah {
            foreach ($scoreMap as $componentId => $score) {
                $komponen = $rubrikKomponens->get($componentId);

                PenilaianKonversiDetail::query()->updateOrCreate(
                    [
                        'konversi_mata_kuliah_id' => $konversi->id,
                        'rubrik_komponen_id' => $componentId,
                    ],
                    [
                        'bobot_persen_snapshot' => $komponen->bobot_persen,
                        'nilai' => $score,
                    ],
                );
            }

            $konversi->load('penilaianDetails');
            $weightedScore = $konversi->calculateWeightedScore();
            $gradeLetter = $weightedScore !== null
                ? KonversiMataKuliah::scoreToGradeLetter($weightedScore)
                : null;

            $konversi->forceFill([
                'nilai_instansi' => $nilaiInstansi,
                'nilai_dosen' => $weightedScore,
                'nilai_akhir' => $weightedScore,
                'nilai_huruf' => $gradeLetter,
                'catatan_dosen' => $catatanDosen,
                'status_penilaian' => $publish ? StatusPenilaian::Dipublikasikan : StatusPenilaian::Draf,
                'is_locked' => $publish,
                'published_at' => $publish ? now() : null,
            ])->save();

            if ($publish) {
                $pendataan = $konversi->pendataanMagang;
                $hasUnpublished = $pendataan->konversiMataKuliahs()
                    ->where('status_penilaian', '!=', StatusPenilaian::Dipublikasikan)
                    ->exists();

                if (! $hasUnpublished) {
                    $pendataan->forceFill([
                        'status_pendataan' => StatusPendataan::Selesai,
                    ])->save();
                }
            }

            $this->auditLogger->record(
                action: $publish ? 'penilaian.published' : 'penilaian.draft_saved',
                user: $actor,
                request: $request,
                properties: [
                    'pendataan_magang_id' => $konversi->pendataan_magang_id,
                    'mata_kuliah_id' => $konversi->mata_kuliah_id,
                    'nilai_akhir' => $konversi->nilai_akhir,
                    'nilai_huruf' => $konversi->nilai_huruf,
                    'is_locked' => $konversi->is_locked,
                ],
                subject: $konversi,
            );

            return $konversi->fresh([
                'mataKuliah.rubrikKomponens',
                'penilaianDetails.rubrikKomponen',
                'pendataanMagang.mahasiswa',
                'dosenPenilai',
            ]);
        });
    }
}
