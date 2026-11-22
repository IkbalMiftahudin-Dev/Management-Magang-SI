<?php

use App\Http\Controllers\Auth\SsoController;
use App\Http\Controllers\DashboardShellController;
use App\Http\Controllers\HomeRedirectController;
use App\Http\Controllers\Mahasiswa\BerandaController;
use App\Http\Controllers\Mahasiswa\PendataanController;
use App\Http\Controllers\ManageLowonganController;
use App\Http\Controllers\PrivateFileController;
use App\Http\Controllers\VerifikasiProdiController;
use Illuminate\Support\Facades\Route;

// Public SSO protocol endpoints & post-logout page (root "/" requires authentication).
Route::get('/sso/login', [SsoController::class, 'login'])
    ->middleware('throttle:30,1')
    ->name('login');

Route::get('/auth/callback', [SsoController::class, 'callback'])
    ->middleware('throttle:30,1')
    ->name('sso.callback');

Route::get('/logged-out', [SsoController::class, 'loggedOut'])
    ->name('sso.logged-out');

// Authenticated application routes protected by Laravel auth + active SSO token session.
Route::middleware(['auth', 'sso.session'])->group(function (): void {
    Route::get('/', HomeRedirectController::class)->name('home');

    Route::post('/logout', [SsoController::class, 'logout'])->name('logout');

    // Policy-protected private file streaming endpoints (never exposed in public/storage).
    Route::prefix('files')->name('files.')->group(function (): void {
        Route::get('/pendataan/{pendataan}/{slot}', [PrivateFileController::class, 'pendataanSlot'])->name('pendataan.slot');
        Route::get('/logbook/{logbook}', [PrivateFileController::class, 'logbook'])->name('logbook');
        Route::get('/sk/{dokumenSk}', [PrivateFileController::class, 'dokumenSk'])->name('sk');
    });

    Route::prefix('mahasiswa')
        ->name('mahasiswa.')
        ->middleware('role:Mahasiswa')
        ->group(function (): void {
            Route::get('/beranda', [BerandaController::class, 'index'])->name('beranda');
            Route::get('/lowongan/{lowongan}', [BerandaController::class, 'show'])->name('lowongan.show');

            Route::get('/pendataan', [PendataanController::class, 'show'])->name('pendataan.show');
            Route::post('/pendataan/step-1', [PendataanController::class, 'saveStepOne'])->name('pendataan.step1');
            Route::post('/pendataan/step-2', [PendataanController::class, 'saveStepTwo'])->name('pendataan.step2');
            Route::post('/pendataan/step-3', [PendataanController::class, 'saveStepThree'])->name('pendataan.step3');
            Route::post('/pendataan/step-4', [PendataanController::class, 'submitStepFour'])->name('pendataan.step4');
        });

    Route::prefix('dosen')
        ->name('dosen.')
        ->middleware('role:Dosen')
        ->group(function (): void {
            Route::get('/penilaian', [DashboardShellController::class, 'dosen'])->name('penilaian.index');
        });

    Route::prefix('staff')
        ->name('staff.')
        ->middleware('role:Staff_Kaprodi')
        ->group(function (): void {
            Route::get('/verifikasi', [VerifikasiProdiController::class, 'index'])->name('verifikasi.index');
            Route::get('/verifikasi/{pendataan}', [VerifikasiProdiController::class, 'show'])->name('verifikasi.show');
            Route::patch('/verifikasi/{pendataan}/status', [VerifikasiProdiController::class, 'updateStatus'])->name('verifikasi.status');
            Route::patch('/verifikasi/{pendataan}/pembimbing-lapangan', [VerifikasiProdiController::class, 'assignPembimbingLapangan'])->name('verifikasi.pembimbing-lapangan');
            Route::post('/verifikasi/{pendataan}/generate-sk', [VerifikasiProdiController::class, 'generateSk'])->name('verifikasi.generate-sk');
            Route::post('/verifikasi/generate-sk-bulk', [VerifikasiProdiController::class, 'generateBulkSk'])->name('verifikasi.generate-sk-bulk');

            Route::get('/lowongan', [ManageLowonganController::class, 'index'])->name('lowongan.index');
            Route::post('/lowongan', [ManageLowonganController::class, 'store'])->name('lowongan.store');
            Route::put('/lowongan/{lowongan}', [ManageLowonganController::class, 'update'])->name('lowongan.update');
            Route::delete('/lowongan/{lowongan}', [ManageLowonganController::class, 'destroy'])->name('lowongan.destroy');
        });

    Route::prefix('kaprodi')
        ->name('kaprodi.')
        ->middleware('role:Kaprodi')
        ->group(function (): void {
            Route::get('/verifikasi', [VerifikasiProdiController::class, 'index'])->name('verifikasi.index');
            Route::get('/verifikasi/{pendataan}', [VerifikasiProdiController::class, 'show'])->name('verifikasi.show');
            Route::patch('/verifikasi/{pendataan}/status', [VerifikasiProdiController::class, 'updateStatus'])->name('verifikasi.status');
            Route::patch('/verifikasi/{pendataan}/pembimbing-lapangan', [VerifikasiProdiController::class, 'assignPembimbingLapangan'])->name('verifikasi.pembimbing-lapangan');
            Route::post('/verifikasi/{pendataan}/generate-sk', [VerifikasiProdiController::class, 'generateSk'])->name('verifikasi.generate-sk');
            Route::post('/verifikasi/generate-sk-bulk', [VerifikasiProdiController::class, 'generateBulkSk'])->name('verifikasi.generate-sk-bulk');
            Route::post('/sk/{dokumenSk}/approve', [VerifikasiProdiController::class, 'approveSk'])
                ->middleware('sso.fresh')
                ->name('sk.approve');

            Route::get('/lowongan', [ManageLowonganController::class, 'index'])->name('lowongan.index');
            Route::post('/lowongan', [ManageLowonganController::class, 'store'])->name('lowongan.store');
            Route::put('/lowongan/{lowongan}', [ManageLowonganController::class, 'update'])->name('lowongan.update');
            Route::delete('/lowongan/{lowongan}', [ManageLowonganController::class, 'destroy'])->name('lowongan.destroy');
        });

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:Admin')
        ->group(function (): void {
            Route::get('/dashboard', [DashboardShellController::class, 'admin'])->name('dashboard');
            Route::get('/lowongan', [ManageLowonganController::class, 'index'])->name('lowongan.index');
            Route::post('/lowongan', [ManageLowonganController::class, 'store'])->name('lowongan.store');
            Route::put('/lowongan/{lowongan}', [ManageLowonganController::class, 'update'])->name('lowongan.update');
            Route::delete('/lowongan/{lowongan}', [ManageLowonganController::class, 'destroy'])->name('lowongan.destroy');
        });
});
