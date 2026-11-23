<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\LowonganMagang;
use App\Services\LowonganService;
use App\Services\SsoSessionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function __construct(
        private readonly LowonganService $lowonganService,
        private readonly SsoSessionManager $sessionManager,
    ) {}

    /**
     * Display active internship vacancies for students with search and source filtering.
     */
    public function index(Request $request): View|JsonResponse
    {
        Gate::authorize('viewAny', LowonganMagang::class);

        $search = is_string($request->query('search')) ? $request->query('search') : null;
        $sumber = is_string($request->query('sumber')) ? $request->query('sumber') : null;

        $lowongans = $this->lowonganService->listForMahasiswa($search, $sumber);

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $lowongans,
                'meta' => [
                    'total' => $lowongans->count(),
                    'search' => $search,
                    'sumber' => $sumber,
                ],
            ]);
        }

        return view('mahasiswa.beranda', [
            'lowongans' => $lowongans,
            'search' => $search,
            'sumber' => $sumber,
        ]);
    }

    /**
     * Display a single active internship vacancy detail.
     */
    public function show(Request $request, LowonganMagang $lowongan): JsonResponse|View
    {
        Gate::authorize('view', $lowongan);

        $lowongan->load('dosenPenanggungJawab');

        if ($request->expectsJson()) {
            return response()->json(['data' => $lowongan]);
        }

        return view('mahasiswa.lowongan-show', [
            'lowongan' => $lowongan,
        ]);
    }
}
