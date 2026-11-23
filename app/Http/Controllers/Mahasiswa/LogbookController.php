<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Logbook\StoreLogbookRequest;
use App\Models\Logbook;
use App\Models\PendataanMagang;
use App\Services\LogbookService;
use App\Services\PendataanMagangService;
use App\Services\SsoSessionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LogbookController extends Controller
{
    public function __construct(
        private readonly PendataanMagangService $pendataanService,
        private readonly LogbookService $logbookService,
        private readonly SsoSessionManager $sessionManager,
    ) {}

    /**
     * Display the student's digital 3-column logbook list and entry form.
     */
    public function index(Request $request): View|JsonResponse
    {
        $mahasiswa = $request->user()?->mahasiswa;
        abort_if($mahasiswa === null, 403, 'Profil mahasiswa tidak ditemukan.');

        $pendataan = $this->pendataanService->findLatestForStudent($mahasiswa);
        $logbooks = $pendataan !== null ? $this->logbookService->listForPendataan($pendataan) : collect();

        if ($request->expectsJson()) {
            return response()->json([
                'pendataan' => $pendataan,
                'data' => $logbooks,
            ]);
        }

        return view('mahasiswa.logbook', [
            'pendataan' => $pendataan,
            'logbooks' => $logbooks,
        ]);
    }

    /**
     * Store a new logbook entry with optional private photo evidence.
     */
    public function store(StoreLogbookRequest $request): RedirectResponse|JsonResponse
    {
        $pendataan = $this->resolveStudentPendataanOrAbort($request);
        Gate::authorize('create', [Logbook::class, $pendataan]);

        $logbook = $this->logbookService->create(
            actor: $request->user(),
            pendataan: $pendataan,
            data: $request->validated(),
            fotoBukti: $request->file('foto_bukti'),
            request: $request,
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $logbook], 201);
        }

        return back()->with('status', 'Kegiatan logbook berhasil disimpan.');
    }

    /**
     * Update an unvalidated logbook entry.
     */
    public function update(StoreLogbookRequest $request, Logbook $logbook): RedirectResponse|JsonResponse
    {
        Gate::authorize('update', $logbook);

        $updated = $this->logbookService->update(
            actor: $request->user(),
            logbook: $logbook,
            data: $request->validated(),
            fotoBukti: $request->file('foto_bukti'),
            request: $request,
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $updated]);
        }

        return back()->with('status', 'Logbook berhasil diperbarui.');
    }

    /**
     * Delete an unvalidated logbook entry.
     */
    public function destroy(Request $request, Logbook $logbook): RedirectResponse|JsonResponse
    {
        Gate::authorize('delete', $logbook);

        $this->logbookService->delete(
            actor: $request->user(),
            logbook: $logbook,
            request: $request,
        );

        if ($request->expectsJson()) {
            return response()->json(['deleted' => true]);
        }

        return back()->with('status', 'Entri logbook berhasil dihapus.');
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
