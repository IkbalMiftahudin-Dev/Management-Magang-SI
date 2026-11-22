<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * All named abilities governed by the Modul 5 RBAC permission matrix.
     *
     * @var list<string>
     */
    private const ABILITIES = [
        'view-lowongan',
        'manage-lowongan',
        'submit-pendataan',
        'verify-pendataan',
        'plot-pembimbing-lapangan',
        'generate-draft-sk',
        'approve-sk',
        'fill-logbook',
        'validate-logbook',
        'submit-berkas-akhir',
        'view-bimbingan-berkas',
        'grade-konversi',
        'view-all-pendataan',
        'view-rekap-penilaian',
        'download-own-sk',
        'download-bimbingan-sk',
        'download-all-sk',
        'view-own-grades',
        'manage-master-data',
        'view-audit-logs',
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach (self::ABILITIES as $ability) {
            Gate::define(
                $ability,
                static fn (User $user): bool => $user->role !== null && $user->role->hasPermission($ability),
            );
        }
    }
}
