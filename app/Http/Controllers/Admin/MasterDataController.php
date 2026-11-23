<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveDosenRequest;
use App\Http\Requests\Admin\SaveMahasiswaRequest;
use App\Http\Requests\Admin\SaveMataKuliahRequest;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Services\LowonganService;
use App\Services\MasterDataService;
use App\Services\SsoSessionManager;
use App\Services\VerifikasiProdiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MasterDataController extends Controller
{
    public function __construct(
        private readonly MasterDataService $masterDataService,
        private readonly LowonganService $lowonganService,
        private readonly VerifikasiProdiService $verifikasiService,
        private readonly SsoSessionManager $sessionManager,
    ) {}

    /**
     * System governance dashboard (read-only recap metrics + master data counts).
     */
    public function dashboard(Request $request): View|JsonResponse
    {
        Gate::authorize('manage-master-data');

        $summary = [
            'total_mata_kuliah' => MataKuliah::query()->count(),
            'total_dosen' => Dosen::query()->count(),
            'total_mahasiswa' => Mahasiswa::query()->count(),
            'lowongan' => $this->lowonganService->managementMetrics(),
            'verifikasi_readonly' => $this->verifikasiService->dashboardMetrics(),
        ];

        if ($request->expectsJson()) {
            return response()->json(['summary' => $summary]);
        }

        return view('admin.dashboard', [
            'summary' => $summary,
        ]);
    }

    public function mataKuliahIndex(Request $request): JsonResponse|View
    {
        Gate::authorize('manage-master-data');

        $courses = MataKuliah::query()
            ->with(['dosenPengampu', 'rubrikKomponens'])
            ->orderBy('kode_mk')
            ->get();

        if ($request->expectsJson()) {
            return response()->json(['data' => $courses]);
        }

        return view('admin.mata-kuliah', [
            'courses' => $courses,
            'dosens' => Dosen::query()->orderBy('nama')->get(),
        ]);
    }

    public function mataKuliahStore(SaveMataKuliahRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $rubrik = $validated['rubrik_komponens'];
        unset($validated['rubrik_komponens']);

        $course = $this->masterDataService->saveMataKuliahWithRubrik(
            actor: $request->user(),
            courseData: $validated,
            rubrikItems: $rubrik,
            existing: null,
            request: $request,
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $course], 201);
        }

        return back()->with('status', 'Mata kuliah konversi berhasil ditambahkan.');
    }

    public function mataKuliahUpdate(SaveMataKuliahRequest $request, MataKuliah $mataKuliah): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $rubrik = $validated['rubrik_komponens'];
        unset($validated['rubrik_komponens']);

        $updated = $this->masterDataService->saveMataKuliahWithRubrik(
            actor: $request->user(),
            courseData: $validated,
            rubrikItems: $rubrik,
            existing: $mataKuliah,
            request: $request,
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $updated]);
        }

        return back()->with('status', 'Mata kuliah konversi berhasil diperbarui.');
    }

    public function dosenStore(SaveDosenRequest $request): RedirectResponse|JsonResponse
    {
        $dosen = $this->masterDataService->saveDosen(
            actor: $request->user(),
            data: $request->validated(),
            existing: null,
            request: $request,
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $dosen], 201);
        }

        return back()->with('status', 'Data dosen berhasil ditambahkan.');
    }

    public function mahasiswaStore(SaveMahasiswaRequest $request): RedirectResponse|JsonResponse
    {
        $mahasiswa = $this->masterDataService->saveMahasiswa(
            actor: $request->user(),
            data: $request->validated(),
            existing: null,
            request: $request,
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $mahasiswa], 201);
        }

        return back()->with('status', 'Data mahasiswa berhasil ditambahkan.');
    }

    public function auditLogsIndex(Request $request): JsonResponse|View
    {
        Gate::authorize('view-audit-logs');

        $logs = $this->masterDataService->listAuditLogs(
            action: is_string($request->query('action')) ? $request->query('action') : null,
            role: is_string($request->query('role')) ? $request->query('role') : null,
            search: is_string($request->query('search')) ? $request->query('search') : null,
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $logs]);
        }

        return view('admin.audit-logs', [
            'logs' => $logs,
        ]);
    }
}
