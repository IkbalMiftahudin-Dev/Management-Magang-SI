<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\BerkasAkhir\UploadBerkasSlotRequest;
use App\Models\BerkasAkhir;
use App\Models\PendataanMagang;
use App\Services\BerkasAkhirService;
use App\Services\PendataanMagangService;
use App\Services\SsoSessionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BerkasAkhirController extends Controller
{
    public function __construct(
        private readonly PendataanMagangService $pendataanService,
        private readonly BerkasAkhirService $berkasService,
        private readonly SsoSessionManager $sessionManager,
    ) {}

    /**
     * Show the One-Stop Submission status across all 6 document slots (5 mandatory + 1 optional).
     */
    public function show(Request $request): View|JsonResponse
    {
        $mahasiswa = $request->user()?->mahasiswa;
        abort_if($mahasiswa === null, 403, 'Profil mahasiswa tidak ditemukan.');

        $pendataan = $this->pendataanService->findLatestForStudent($mahasiswa);
        $berkas = $pendataan !== null ? $this->berkasService->getOrCreateForPendataan($pendataan) : null;

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $berkas,
                'progress' => $berkas !== null ? [
                    'mandatory_uploaded' => $berkas->mandatoryUploadedCount(),
                    'mandatory_total' => count(BerkasAkhir::MANDATORY_SLOTS),
                    'total_uploaded' => $berkas->totalUploadedCount(),
                    'total_slots' => count(BerkasAkhir::ALL_SLOTS),
                    'percentage' => $berkas->completionPercentage(),
                    'can_submit' => $berkas->isMandatoryComplete(),
                ] : null,
            ]);
        }

        return view('mahasiswa.berkas-akhir', [
            'pendataan' => $pendataan,
            'berkas' => $berkas,
        ]);
    }

    /**
     * Upload or replace one PDF slot (loa, rubrik_konversi, laporan_akhir, draf_pks, transkrip_instansi, sertifikat).
     */
    public function uploadSlot(UploadBerkasSlotRequest $request, string $slot): RedirectResponse|JsonResponse
    {
        $pendataan = $this->resolveStudentPendataanOrAbort($request);
        Gate::authorize('upload', [BerkasAkhir::class, $pendataan]);

        $berkas = $this->berkasService->uploadSlot(
            actor: $request->user(),
            pendataan: $pendataan,
            slot: $slot,
            file: $request->file('file'),
            request: $request,
        );

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $berkas,
                'progress' => [
                    'mandatory_uploaded' => $berkas->mandatoryUploadedCount(),
                    'total_uploaded' => $berkas->totalUploadedCount(),
                    'percentage' => $berkas->completionPercentage(),
                    'can_submit' => $berkas->isMandatoryComplete(),
                ],
            ]);
        }

        return back()->with('status', 'Berkas berhasil diunggah.');
    }

    /**
     * Finalize One-Stop Submission once all 5 mandatory documents have been uploaded.
     */
    public function submitAll(Request $request): RedirectResponse|JsonResponse
    {
        $pendataan = $this->resolveStudentPendataanOrAbort($request);
        Gate::authorize('submit', [BerkasAkhir::class, $pendataan]);

        $berkas = $this->berkasService->submitAll(
            actor: $request->user(),
            pendataan: $pendataan,
            request: $request,
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $berkas]);
        }

        return back()->with('status', 'Seluruh berkas akhir berhasil dikirim untuk dinilai dosen.');
    }

    private function resolveStudentPendataanOrAbort(Request $request): PendataanMagang
    {
        $mahasiswa = $request->user()?->mahasiswa;
        abort_if($mahasiswa === null, 403, 'Profil mahasiswa tidak ditemukan.');

        $pendataan = $this->pendataanService->findLatestForStudent($mahasiswa);
        abort_if($pendataan === null, 422, 'Anda belum memiliki pendataan magang yang aktif.');

        return $pendataan;
    }
}
