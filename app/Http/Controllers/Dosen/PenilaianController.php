<?php

namespace App\Http\Controllers\Dosen;

use App\Enums\StatusValidasiLogbook;
use App\Http\Controllers\Controller;
use App\Http\Requests\Logbook\ValidateLogbookRequest;
use App\Http\Requests\Penilaian\SavePenilaianRequest;
use App\Models\KonversiMataKuliah;
use App\Models\Logbook;
use App\Services\LogbookService;
use App\Services\PenilaianKonversiService;
use App\Services\SsoSessionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PenilaianController extends Controller
{
    public function __construct(
        private readonly PenilaianKonversiService $penilaianService,
        private readonly LogbookService $logbookService,
        private readonly SsoSessionManager $sessionManager,
    ) {}

    /**
     * Display the lecturer's conversion courses, 4 KPI cards, and list of student applicants.
     */
    public function index(Request $request): View|JsonResponse
    {
        $dosen = $request->user()?->dosen;
        abort_if($dosen === null, 403, 'Profil dosen tidak ditemukan untuk akun ini.');

        $courseId = $request->filled('mata_kuliah_id') ? (int) $request->query('mata_kuliah_id') : null;
        $search = is_string($request->query('search')) ? $request->query('search') : null;

        $payload = $this->penilaianService->dashboardForDosen($dosen, $courseId, $search);

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return view('dosen.penilaian-index', [
            'courses' => $payload['courses'],
            'selectedCourse' => $payload['selected_course'],
            'metrics' => $payload['metrics'],
            'submissions' => $payload['submissions'],
            'search' => $search,
        ]);
    }

    /**
     * Display the Detail Penilaian & SK page with supporting evidence and weighted rubric components.
     */
    public function show(Request $request, KonversiMataKuliah $konversi): View|JsonResponse
    {
        Gate::authorize('view', $konversi);

        $konversi->load([
            'mataKuliah.rubrikKomponens',
            'penilaianDetails.rubrikKomponen',
            'dosenPenilai',
            'pendataanMagang.mahasiswa',
            'pendataanMagang.berkasAkhir',
            'pendataanMagang.logbooks',
            'pendataanMagang.dokumenSks',
        ]);

        if ($request->expectsJson()) {
            return response()->json(['data' => $konversi]);
        }

        return view('dosen.penilaian-detail', [
            'konversi' => $konversi,
        ]);
    }

    /**
     * Save lecturer rubric scores as a mutable draft.
     */
    public function saveDraft(SavePenilaianRequest $request, KonversiMataKuliah $konversi): RedirectResponse|JsonResponse
    {
        $saved = $this->penilaianService->saveEvaluation(
            actor: $request->user(),
            konversi: $konversi,
            scores: $request->validated('scores'),
            nilaiInstansi: $request->validated('nilai_instansi') !== null ? (float) $request->validated('nilai_instansi') : null,
            catatanDosen: $request->validated('catatan_dosen'),
            publish: false,
            request: $request,
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $saved]);
        }

        return back()->with('status', 'Draf penilaian berhasil disimpan.');
    }

    /**
     * Publish and permanently lock final conversion grade (protected by sso.fresh live /userinfo check).
     */
    public function publish(SavePenilaianRequest $request, KonversiMataKuliah $konversi): RedirectResponse|JsonResponse
    {
        Gate::authorize('publish', $konversi);

        $published = $this->penilaianService->saveEvaluation(
            actor: $request->user(),
            konversi: $konversi,
            scores: $request->validated('scores'),
            nilaiInstansi: $request->validated('nilai_instansi') !== null ? (float) $request->validated('nilai_instansi') : null,
            catatanDosen: $request->validated('catatan_dosen'),
            publish: true,
            request: $request,
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $published]);
        }

        return back()->with('status', 'Nilai konversi berhasil dipublikasikan dan dikunci.');
    }

    /**
     * Validate or request revision on a guided student's logbook entry (exclusive to Dosen Pembimbing UPI).
     */
    public function validateLogbook(ValidateLogbookRequest $request, Logbook $logbook): RedirectResponse|JsonResponse
    {
        $dosen = $request->user()?->dosen;
        abort_if($dosen === null, 403, 'Profil dosen tidak ditemukan.');

        $validated = $this->logbookService->validateByDosen(
            actor: $request->user(),
            dosen: $dosen,
            logbook: $logbook,
            status: StatusValidasiLogbook::from($request->validated('status_validasi')),
            catatan: $request->validated('catatan_dosen'),
            request: $request,
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $validated]);
        }

        return back()->with('status', 'Status validasi logbook berhasil diperbarui.');
    }
}
