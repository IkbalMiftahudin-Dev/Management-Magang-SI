<?php

namespace App\Http\Controllers;

use App\Enums\JenisSk;
use App\Enums\StatusPendataan;
use App\Http\Requests\Verifikasi\AssignPembimbingLapanganRequest;
use App\Http\Requests\Verifikasi\GenerateSkRequest;
use App\Http\Requests\Verifikasi\UpdateVerifikasiStatusRequest;
use App\Models\DokumenSk;
use App\Models\Dosen;
use App\Models\PendataanMagang;
use App\Services\SkGeneratorService;
use App\Services\SsoSessionManager;
use App\Services\VerifikasiProdiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class VerifikasiProdiController extends Controller
{
    public function __construct(
        private readonly VerifikasiProdiService $verifikasiService,
        private readonly SkGeneratorService $skGeneratorService,
        private readonly SsoSessionManager $sessionManager,
    ) {}

    /**
     * Display the Verifikasi & Penilaian dashboard with KPI cards and student registration recap.
     */
    public function index(Request $request): View|JsonResponse
    {
        Gate::authorize('verify-pendataan');

        $search = is_string($request->query('search')) ? $request->query('search') : null;
        $status = is_string($request->query('status')) ? $request->query('status') : null;
        $semester = is_string($request->query('semester')) ? $request->query('semester') : null;

        $rekap = $this->verifikasiService->listRekap($search, $status, $semester);
        $metrics = $this->verifikasiService->dashboardMetrics();
        $dosens = Dosen::query()->orderBy('nama')->get();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $rekap,
                'metrics' => $metrics,
                'dosens' => $dosens,
            ]);
        }

        $user = $request->user();

        return view('dashboard.shell', [
            'user' => $user,
            'pageTitle' => 'Verifikasi & Penilaian',
            'pageDescription' => 'Kelola verifikasi pendataan, plotting pembimbing lapangan, dan otomatisasi dokumen SK.',
            'ssoSession' => $this->sessionManager->findForRequest($request, $user),
            'permissions' => $user->role->permissions(),
            'rekap' => $rekap,
            'metrics' => $metrics,
            'dosens' => $dosens,
        ]);
    }

    /**
     * View full details of a single student registration.
     */
    public function show(Request $request, PendataanMagang $pendataan): View|JsonResponse
    {
        Gate::authorize('view', $pendataan);

        $pendataan->load([
            'mahasiswa',
            'dosenPembimbing',
            'dosenKunjung',
            'konversiMataKuliahs.mataKuliah.dosenPengampu',
            'dokumenSks.approver',
            'berkasAkhir',
            'logbooks',
        ]);

        if ($request->expectsJson()) {
            return response()->json(['data' => $pendataan]);
        }

        $user = $request->user();

        return view('dashboard.shell', [
            'user' => $user,
            'pageTitle' => 'Detail Pendataan Mahasiswa',
            'pageDescription' => ($pendataan->mahasiswa?->nama ?? 'Mahasiswa').' · '.$pendataan->nama_instansi,
            'ssoSession' => $this->sessionManager->findForRequest($request, $user),
            'permissions' => $user->role->permissions(),
            'pendataan' => $pendataan,
        ]);
    }

    /**
     * Approve (Terverifikasi) or return for revision (Perlu Revisi) a student registration.
     */
    public function updateStatus(UpdateVerifikasiStatusRequest $request, PendataanMagang $pendataan): RedirectResponse|JsonResponse
    {
        $updated = $this->verifikasiService->updateStatus(
            actor: $request->user(),
            pendataan: $pendataan,
            targetStatus: StatusPendataan::from($request->validated('status')),
            catatan: $request->validated('catatan_verifikasi'),
            request: $request,
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $updated]);
        }

        return back()->with('status', 'Status verifikasi pendataan berhasil diperbarui.');
    }

    /**
     * Assign or update Pembimbing Lapangan / Dosen Kunjung on a student registration.
     */
    public function assignPembimbingLapangan(
        AssignPembimbingLapanganRequest $request,
        PendataanMagang $pendataan,
    ): RedirectResponse|JsonResponse {
        $updated = $this->verifikasiService->assignPembimbingLapangan(
            actor: $request->user(),
            pendataan: $pendataan,
            dosenKunjungId: (int) $request->validated('dosen_kunjung_id'),
            request: $request,
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $updated]);
        }

        return back()->with('status', 'Pembimbing lapangan berhasil ditetapkan.');
    }

    /**
     * Generate a draft SK PDF (SK Magang or SK Kunjung) for a single verified student.
     */
    public function generateSk(GenerateSkRequest $request, PendataanMagang $pendataan): RedirectResponse|JsonResponse
    {
        $dokumenSk = $this->skGeneratorService->generate(
            actor: $request->user(),
            pendataan: $pendataan,
            jenisSk: JenisSk::from($request->validated('jenis_sk')),
            request: $request,
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $dokumenSk], 201);
        }

        return back()->with('status', 'Draf SK '.$dokumenSk->nomor_sk.' berhasil digenerate.');
    }

    /**
     * Bulk-generate missing draft SKs for all verified students (Otomatisasi Generasi SK button).
     */
    public function generateBulkSk(Request $request): RedirectResponse|JsonResponse
    {
        Gate::authorize('generate-draft-sk');

        $summary = $this->skGeneratorService->generateBulkMissingDrafts(
            actor: $request->user(),
            request: $request,
        );

        if ($request->expectsJson()) {
            return response()->json(['summary' => $summary]);
        }

        return back()->with('status', sprintf('Otomatisasi SK selesai: %d draf dibuat, %d sudah ada.', $summary['generated'], $summary['skipped']));
    }

    /**
     * Officially approve a draft SK (exclusive to Kaprodi, protected by sso.fresh live userinfo check).
     */
    public function approveSk(Request $request, DokumenSk $dokumenSk): RedirectResponse|JsonResponse
    {
        Gate::authorize('approve', $dokumenSk);

        $approved = $this->skGeneratorService->approve(
            kaprodi: $request->user(),
            dokumenSk: $dokumenSk,
            request: $request,
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $approved]);
        }

        return back()->with('status', 'SK '.$approved->nomor_sk.' resmi disahkan.');
    }
}
