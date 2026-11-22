<?php

use App\Http\Controllers\Auth\SsoController;
use App\Http\Controllers\DashboardShellController;
use App\Http\Controllers\HomeRedirectController;
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

    Route::prefix('mahasiswa')
        ->name('mahasiswa.')
        ->middleware('role:Mahasiswa')
        ->group(function (): void {
            Route::get('/beranda', [DashboardShellController::class, 'mahasiswa'])->name('beranda');
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
            Route::get('/verifikasi', [DashboardShellController::class, 'staff'])->name('verifikasi.index');
        });

    Route::prefix('kaprodi')
        ->name('kaprodi.')
        ->middleware('role:Kaprodi')
        ->group(function (): void {
            Route::get('/verifikasi', [DashboardShellController::class, 'kaprodi'])->name('verifikasi.index');
        });

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:Admin')
        ->group(function (): void {
            Route::get('/dashboard', [DashboardShellController::class, 'admin'])->name('dashboard');
        });
});
