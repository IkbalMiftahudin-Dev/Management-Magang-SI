<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Enums\StatusPengesahanSk;
use App\Enums\StatusPenilaian;
use App\Http\Controllers\Controller;
use App\Services\PendataanMagangService;
use App\Services\SsoSessionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HasilMagangController extends Controller
{
    public function __construct(
        private readonly PendataanMagangService $pendataanService,
        private readonly SsoSessionManager $sessionManager,
    ) {}

    /**
     * Display the student's approved SK documents and published course conversion grades.
     */
    public function index(Request $request): View|JsonResponse
    {
        $mahasiswa = $request->user()?->mahasiswa;
        abort_if($mahasiswa === null, 403, 'Profil mahasiswa tidak ditemukan.');

        $pendataan = $this->pendataanService->findLatestForStudent($mahasiswa);

        $approvedSks = $pendataan !== null
            ? $pendataan->dokumenSks->where('status_pengesahan', StatusPengesahanSk::Disahkan)->values()
            : collect();

        $publishedGrades = $pendataan !== null
            ? $pendataan->konversiMataKuliahs->where('status_penilaian', StatusPenilaian::Dipublikasikan)->values()
            : collect();

        if ($request->expectsJson()) {
            return response()->json([
                'pendataan' => $pendataan,
                'approved_sks' => $approvedSks,
                'published_grades' => $publishedGrades,
            ]);
        }

        return view('mahasiswa.hasil', [
            'pendataan' => $pendataan,
            'approvedSks' => $approvedSks,
            'publishedGrades' => $publishedGrades,
        ]);
    }
}
