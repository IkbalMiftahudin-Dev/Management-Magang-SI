<?php

namespace App\Http\Controllers;

use App\Services\SsoSessionManager;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardShellController extends Controller
{
    public function __construct(
        private readonly SsoSessionManager $sessionManager,
    ) {}

    public function mahasiswa(Request $request): View
    {
        return $this->renderShell($request, 'Beranda Portal Magang', 'Portal informasi lowongan, pendataan pasca-diterima, logbook digital, dan pengumpulan berkas akhir.');
    }

    public function dosen(Request $request): View
    {
        return $this->renderShell($request, 'Penilaian & SK', 'Kelola validasi logbook bimbingan, peninjauan berkas akhir, dan rubrik penilaian mata kuliah konversi.');
    }

    public function staff(Request $request): View
    {
        return $this->renderShell($request, 'Verifikasi & Operasional Prodi', 'Verifikasi kelengkapan pendataan magang, plotting pembimbing lapangan, dan otomatisasi draf SK.');
    }

    public function kaprodi(Request $request): View
    {
        return $this->renderShell($request, 'Verifikasi & Pengesahan Kaprodi', 'Pengawasan rekapitulasi magang, penetapan pembimbing lapangan, dan pengesahan SK Magang.');
    }

    public function admin(Request $request): View
    {
        return $this->renderShell($request, 'Dashboard Tata Kelola Sistem', 'Pengelolaan master data mata kuliah konversi, lowongan, monitoring rekap, dan audit trail sistem.');
    }

    private function renderShell(Request $request, string $pageTitle, string $pageDescription): View
    {
        $user = $request->user();
        $ssoSession = $this->sessionManager->findForRequest($request, $user);

        return view('dashboard.shell', [
            'user' => $user,
            'pageTitle' => $pageTitle,
            'pageDescription' => $pageDescription,
            'ssoSession' => $ssoSession,
            'permissions' => $user->role->permissions(),
        ]);
    }
}
