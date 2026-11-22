<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lowongan\StoreLowonganRequest;
use App\Http\Requests\Lowongan\UpdateLowonganRequest;
use App\Models\Dosen;
use App\Models\LowonganMagang;
use App\Services\LowonganService;
use App\Services\SsoSessionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ManageLowonganController extends Controller
{
    public function __construct(
        private readonly LowonganService $lowonganService,
        private readonly SsoSessionManager $sessionManager,
    ) {}

    /**
     * List all vacancies and KPI metrics for Staff_Kaprodi, Kaprodi, and Admin.
     */
    public function index(Request $request): View|JsonResponse
    {
        Gate::authorize('manage', LowonganMagang::class);

        $search = is_string($request->query('search')) ? $request->query('search') : null;
        $sumber = is_string($request->query('sumber')) ? $request->query('sumber') : null;
        $status = is_string($request->query('status')) ? $request->query('status') : null;

        $lowongans = $this->lowonganService->listForManagement($search, $sumber, $status);
        $metrics = $this->lowonganService->managementMetrics();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $lowongans,
                'metrics' => $metrics,
            ]);
        }

        $user = $request->user();

        return view('dashboard.shell', [
            'user' => $user,
            'pageTitle' => 'Kelola Lowongan',
            'pageDescription' => 'Publikasikan dan perbarui informasi lowongan magang untuk mahasiswa.',
            'ssoSession' => $this->sessionManager->findForRequest($request, $user),
            'permissions' => $user->role->permissions(),
            'lowongans' => $lowongans,
            'metrics' => $metrics,
            'dosens' => Dosen::query()->orderBy('nama')->get(),
        ]);
    }

    /**
     * Store a newly published or draft vacancy.
     */
    public function store(StoreLowonganRequest $request): RedirectResponse|JsonResponse
    {
        $lowongan = $this->lowonganService->create(
            actor: $request->user(),
            data: $request->validated(),
            request: $request,
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $lowongan], 201);
        }

        return back()->with('status', 'Lowongan berhasil dipublikasikan.');
    }

    /**
     * Update an existing vacancy.
     */
    public function update(UpdateLowonganRequest $request, LowonganMagang $lowongan): RedirectResponse|JsonResponse
    {
        $updated = $this->lowonganService->update(
            actor: $request->user(),
            lowongan: $lowongan,
            data: $request->validated(),
            request: $request,
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $updated]);
        }

        return back()->with('status', 'Lowongan berhasil diperbarui.');
    }

    /**
     * Delete a vacancy.
     */
    public function destroy(Request $request, LowonganMagang $lowongan): RedirectResponse|JsonResponse
    {
        Gate::authorize('delete', $lowongan);

        $this->lowonganService->delete(
            actor: $request->user(),
            lowongan: $lowongan,
            request: $request,
        );

        if ($request->expectsJson()) {
            return response()->json(['deleted' => true]);
        }

        return back()->with('status', 'Lowongan berhasil dihapus.');
    }
}
