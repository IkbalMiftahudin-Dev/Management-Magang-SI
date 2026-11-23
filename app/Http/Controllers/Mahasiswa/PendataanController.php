<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Enums\JenisProgramMagang;
use App\Enums\ModeKegiatan;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pendataan\SaveStepOneRequest;
use App\Http\Requests\Pendataan\SaveStepThreeRequest;
use App\Http\Requests\Pendataan\SaveStepTwoRequest;
use App\Http\Requests\Pendataan\SubmitStepFourRequest;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\PendataanMagang;
use App\Services\PendataanMagangService;
use App\Services\SsoSessionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PendataanController extends Controller
{
    public function __construct(
        private readonly PendataanMagangService $pendataanService,
        private readonly SsoSessionManager $sessionManager,
    ) {}

    /**
     * Show the student's current 4-step internship registration draft or submitted record.
     */
    public function show(Request $request): View|JsonResponse
    {
        $mahasiswa = $this->resolveMahasiswaOrAbort($request);
        $pendataan = $this->pendataanService->findLatestForStudent($mahasiswa);

        if ($pendataan !== null) {
            Gate::authorize('view', $pendataan);
        }

        $dosens = Dosen::query()->orderBy('nama')->get();
        $mataKuliahs = MataKuliah::query()
            ->with('dosenPengampu')
            ->where('is_active', true)
            ->orderBy('kode_mk')
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $pendataan,
                'options' => [
                    'programs' => array_map(static fn (JenisProgramMagang $program): array => [
                        'value' => $program->value,
                        'code' => $program->code(),
                        'label' => $program->label(),
                        'description' => $program->description(),
                    ], JenisProgramMagang::cases()),
                    'modes' => array_map(static fn (ModeKegiatan $mode): array => [
                        'value' => $mode->value,
                        'label' => $mode->label(),
                    ], ModeKegiatan::cases()),
                    'dosens' => $dosens,
                    'mata_kuliahs' => $mataKuliahs,
                ],
            ]);
        }

        $user = $request->user();

        return view('mahasiswa.pendataan', [
            'user' => $user,
            'pendataan' => $pendataan,
            'dosens' => $dosens,
            'mataKuliahs' => $mataKuliahs,
        ]);
    }

    /**
     * Step 1: Save Data Instansi.
     */
    public function saveStepOne(SaveStepOneRequest $request): RedirectResponse|JsonResponse
    {
        $mahasiswa = $this->resolveMahasiswaOrAbort($request);
        $existing = $this->pendataanService->findLatestForStudent($mahasiswa);

        if ($existing !== null) {
            Gate::authorize('update', $existing);
        } else {
            Gate::authorize('create', PendataanMagang::class);
        }

        $pendataan = $this->pendataanService->saveStepOne(
            mahasiswa: $mahasiswa,
            data: $request->validated(),
            existing: $existing,
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $pendataan, 'next_step' => 2], $existing === null ? 201 : 200);
        }

        return redirect()->route('mahasiswa.pendataan.show', ['step' => 2])
            ->with('status', 'Data instansi berhasil disimpan.');
    }

    /**
     * Step 2: Save Program, Periode, & mandatory LOA upload (synced automatically to BerkasAkhir).
     */
    public function saveStepTwo(SaveStepTwoRequest $request): RedirectResponse|JsonResponse
    {
        $pendataan = $this->resolveEditablePendataanOrAbort($request);

        $updated = $this->pendataanService->saveStepTwo(
            pendataan: $pendataan,
            data: $request->validated(),
            loaFile: $request->file('loa'),
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $updated, 'next_step' => 3]);
        }

        return redirect()->route('mahasiswa.pendataan.show', ['step' => 3])
            ->with('status', 'Program, periode, dan LOA berhasil disimpan.');
    }

    /**
     * Step 3: Save Pemetaan Konversi Mata Kuliah.
     */
    public function saveStepThree(SaveStepThreeRequest $request): RedirectResponse|JsonResponse
    {
        $pendataan = $this->resolveEditablePendataanOrAbort($request);

        $updated = $this->pendataanService->saveStepThree(
            pendataan: $pendataan,
            mataKuliahIds: $request->validated('mata_kuliah_ids'),
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $updated, 'next_step' => 4]);
        }

        return redirect()->route('mahasiswa.pendataan.show', ['step' => 4])
            ->with('status', 'Pemetaan konversi mata kuliah berhasil disimpan.');
    }

    /**
     * Step 4: Final confirmation and submission for Staff Prodi / Kaprodi verification.
     */
    public function submitStepFour(SubmitStepFourRequest $request): RedirectResponse|JsonResponse
    {
        $pendataan = $this->resolveEditablePendataanOrAbort($request);

        $submitted = $this->pendataanService->submitStepFour(
            actor: $request->user(),
            pendataan: $pendataan,
            pernyataanDisetujui: (bool) $request->boolean('pernyataan_disetujui'),
            request: $request,
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $submitted]);
        }

        return redirect()->route('mahasiswa.pendataan.show')
            ->with('status', 'Pendataan berhasil dikirim untuk diverifikasi.');
    }

    private function resolveMahasiswaOrAbort(Request $request): Mahasiswa
    {
        $mahasiswa = $request->user()?->mahasiswa;
        abort_if($mahasiswa === null, 403, 'Profil mahasiswa tidak ditemukan untuk akun ini.');

        return $mahasiswa;
    }

    private function resolveEditablePendataanOrAbort(Request $request): PendataanMagang
    {
        $mahasiswa = $this->resolveMahasiswaOrAbort($request);
        $pendataan = $this->pendataanService->findLatestForStudent($mahasiswa);

        abort_if($pendataan === null, 422, 'Silakan lengkapi Langkah 1 (Data Instansi) terlebih dahulu.');
        Gate::authorize('update', $pendataan);

        return $pendataan;
    }
}
