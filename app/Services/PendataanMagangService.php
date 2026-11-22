<?php

namespace App\Services;

use App\Enums\StatusBerkasAkhir;
use App\Enums\StatusPendataan;
use App\Enums\StatusPenilaian;
use App\Enums\TingkatKesesuaian;
use App\Models\BerkasAkhir;
use App\Models\KonversiMataKuliah;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\PendataanMagang;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PendataanMagangService
{
    public function __construct(
        private readonly PrivateFileService $fileService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Fetch the latest pendataan record for a student with all related entities eager loaded.
     */
    public function findLatestForStudent(Mahasiswa $mahasiswa): ?PendataanMagang
    {
        return PendataanMagang::query()
            ->where('mahasiswa_id', $mahasiswa->id)
            ->with([
                'dosenPembimbing',
                'dosenKunjung',
                'konversiMataKuliahs.mataKuliah.dosenPengampu',
                'berkasAkhir',
                'dokumenSks',
            ])
            ->latest('id')
            ->first();
    }

    /**
     * Save Step 1 (Data Instansi) creating a draft pendataan if none exists yet.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveStepOne(Mahasiswa $mahasiswa, array $data, ?PendataanMagang $existing = null): PendataanMagang
    {
        $pendataan = $existing ?? new PendataanMagang([
            'mahasiswa_id' => $mahasiswa->id,
            'status_pendataan' => StatusPendataan::Draft,
            'current_step' => 1,
        ]);

        $pendataan->fill([
            'nama_instansi' => $data['nama_instansi'],
            'posisi' => $data['posisi'],
            'alamat_instansi' => $data['alamat_instansi'],
            'kontak_instansi' => $data['kontak_instansi'],
            'semester_akademik' => $data['semester_akademik'] ?? ($pendataan->semester_akademik ?: $this->defaultSemesterLabel()),
            'current_step' => max((int) $pendataan->current_step, 2),
        ]);

        $pendataan->save();

        return $pendataan->fresh(['dosenPembimbing', 'konversiMataKuliahs.mataKuliah.dosenPengampu']);
    }

    /**
     * Save Step 2 (Program & Periode + mandatory LOA upload) and auto-sync LOA into berkas_akhirs slot 01.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveStepTwo(PendataanMagang $pendataan, array $data, ?UploadedFile $loaFile = null): PendataanMagang
    {
        if ($loaFile === null && ($pendataan->loa_path === null || $pendataan->loa_path === '')) {
            throw ValidationException::withMessages([
                'loa' => ['Letter of Acceptance (LOA) wajib diunggah pada langkah Program & Periode.'],
            ]);
        }

        return DB::transaction(function () use ($pendataan, $data, $loaFile): PendataanMagang {
            $oldLoaPath = $pendataan->loa_path;
            $newLoaPath = $oldLoaPath;
            $loaUploadedAt = $pendataan->loa_uploaded_at;

            if ($loaFile !== null) {
                $stored = $this->fileService->storeUploadedFile($loaFile, 'loa');
                $newLoaPath = $stored['path'];
                $loaUploadedAt = now();
            }

            $pendataan->fill([
                'jenis_program' => $data['jenis_program'],
                'dosen_pembimbing_id' => $data['dosen_pembimbing_id'],
                'mode_kegiatan' => $data['mode_kegiatan'],
                'tanggal_mulai' => $data['tanggal_mulai'],
                'tanggal_selesai' => $data['tanggal_selesai'],
                'loa_path' => $newLoaPath,
                'loa_uploaded_at' => $loaUploadedAt,
                'current_step' => max((int) $pendataan->current_step, 3),
            ]);

            $pendataan->save();

            // Automatically sync Step 2 LOA into BerkasAkhir Slot 01 so students do not have to upload twice.
            BerkasAkhir::query()->updateOrCreate(
                ['pendataan_magang_id' => $pendataan->id],
                [
                    'loa_path' => $newLoaPath,
                    'loa_uploaded_at' => $loaUploadedAt,
                ],
            );

            if ($loaFile !== null && $oldLoaPath !== null && $oldLoaPath !== $newLoaPath) {
                $this->fileService->deleteIfExists($oldLoaPath);
            }

            return $pendataan->fresh(['dosenPembimbing', 'berkasAkhir', 'konversiMataKuliahs.mataKuliah.dosenPengampu']);
        });
    }

    /**
     * Save Step 3 (Pemetaan Konversi Mata Kuliah) with SKS snapshot and automatic lecturer assignment.
     *
     * @param  list<int>  $mataKuliahIds
     */
    public function saveStepThree(PendataanMagang $pendataan, array $mataKuliahIds): PendataanMagang
    {
        $uniqueIds = array_values(array_unique(array_map('intval', $mataKuliahIds)));

        $activeCourses = MataKuliah::query()
            ->whereIn('id', $uniqueIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        if ($activeCourses->count() !== count($uniqueIds)) {
            throw ValidationException::withMessages([
                'mata_kuliah_ids' => ['Salah satu mata kuliah yang dipilih tidak aktif atau tidak ditemukan.'],
            ]);
        }

        return DB::transaction(function () use ($pendataan, $uniqueIds, $activeCourses): PendataanMagang {
            $pendataan->konversiMataKuliahs()
                ->whereNotIn('mata_kuliah_id', $uniqueIds)
                ->delete();

            foreach ($uniqueIds as $courseId) {
                /** @var MataKuliah $course */
                $course = $activeCourses->get($courseId);

                KonversiMataKuliah::query()->updateOrCreate(
                    [
                        'pendataan_magang_id' => $pendataan->id,
                        'mata_kuliah_id' => $course->id,
                    ],
                    [
                        'dosen_penilai_id' => $course->dosen_pengampu_id,
                        'sks_konversi' => $course->sks,
                        'tingkat_kesesuaian' => TingkatKesesuaian::Sesuai,
                        'status_penilaian' => StatusPenilaian::BelumDinilai,
                    ],
                );
            }

            $pendataan->forceFill([
                'current_step' => 4,
            ])->save();

            return $pendataan->fresh(['dosenPembimbing', 'konversiMataKuliahs.mataKuliah.dosenPengampu']);
        });
    }

    /**
     * Finalize Step 4 (Konfirmasi Pendataan) and submit for Staff Prodi / Kaprodi verification.
     */
    public function submitStepFour(
        User $actor,
        PendataanMagang $pendataan,
        bool $pernyataanDisetujui,
        ?Request $request = null,
    ): PendataanMagang {
        if (! $pernyataanDisetujui) {
            throw ValidationException::withMessages([
                'pernyataan_disetujui' => ['Anda wajib menyetujui pernyataan kebenaran data sebelum mengirim pendataan.'],
            ]);
        }

        $missingFields = $this->validateCompletenessBeforeSubmit($pendataan);

        if ($missingFields !== []) {
            throw ValidationException::withMessages($missingFields);
        }

        return DB::transaction(function () use ($actor, $pendataan, $request): PendataanMagang {
            $pendataan->forceFill([
                'pernyataan_disetujui' => true,
                'status_pendataan' => StatusPendataan::MenungguVerifikasi,
                'catatan_verifikasi' => null,
                'submitted_at' => now(),
                'current_step' => 4,
            ])->save();

            BerkasAkhir::query()->updateOrCreate(
                ['pendataan_magang_id' => $pendataan->id],
                [
                    'loa_path' => $pendataan->loa_path,
                    'loa_uploaded_at' => $pendataan->loa_uploaded_at ?? now(),
                    'status_kelengkapan' => StatusBerkasAkhir::BelumLengkap,
                ],
            );

            $this->auditLogger->record(
                action: 'pendataan.submitted',
                user: $actor,
                request: $request,
                properties: [
                    'nama_instansi' => $pendataan->nama_instansi,
                    'jenis_program' => $pendataan->jenis_program?->value,
                    'total_sks' => $pendataan->totalConvertedSks(),
                ],
                subject: $pendataan,
            );

            return $pendataan->fresh([
                'mahasiswa',
                'dosenPembimbing',
                'konversiMataKuliahs.mataKuliah.dosenPengampu',
                'berkasAkhir',
            ]);
        });
    }

    /**
     * Verify all required steps 1-3 are complete prior to Step 4 submission.
     *
     * @return array<string, list<string>>
     */
    private function validateCompletenessBeforeSubmit(PendataanMagang $pendataan): array
    {
        $errors = [];

        if (trim((string) $pendataan->nama_instansi) === '' || trim((string) $pendataan->posisi) === '') {
            $errors['step_1'] = ['Data instansi pada Langkah 1 belum lengkap.'];
        }

        if (
            $pendataan->jenis_program === null
            || $pendataan->dosen_pembimbing_id === null
            || $pendataan->mode_kegiatan === null
            || $pendataan->tanggal_mulai === null
            || $pendataan->tanggal_selesai === null
            || $pendataan->loa_path === null
        ) {
            $errors['step_2'] = ['Program, periode, dosen pembimbing, dan unggahan LOA pada Langkah 2 wajib dilengkapi.'];
        }

        if ($pendataan->konversiMataKuliahs()->count() < 1) {
            $errors['step_3'] = ['Minimal 1 mata kuliah konversi wajib dipetakan pada Langkah 3.'];
        }

        return $errors;
    }

    private function defaultSemesterLabel(): string
    {
        $year = now()->year;
        $isEven = now()->month >= 2 && now()->month <= 7;

        return $isEven
            ? sprintf('Semester Genap %d/%d', $year - 1, $year)
            : sprintf('Semester Ganjil %d/%d', $year, $year + 1);
    }
}
