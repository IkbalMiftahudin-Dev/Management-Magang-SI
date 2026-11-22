<?php

namespace App\Policies;

use App\Enums\StatusPendataan;
use App\Enums\StatusPengesahanSk;
use App\Enums\UserRole;
use App\Models\DokumenSk;
use App\Models\PendataanMagang;
use App\Models\User;

class DokumenSkPolicy
{
    public function download(User $user, DokumenSk $dokumenSk): bool
    {
        if ($user->role === null) {
            return false;
        }

        if ($user->role->hasPermission('download-all-sk')) {
            return true;
        }

        if ($dokumenSk->status_pengesahan !== StatusPengesahanSk::Disahkan) {
            return false;
        }

        $pendataan = $dokumenSk->pendataanMagang;

        if ($pendataan === null) {
            return false;
        }

        if ($user->role === UserRole::Mahasiswa && $user->mahasiswa !== null) {
            return $pendataan->mahasiswa_id === $user->mahasiswa->id;
        }

        if ($user->role === UserRole::Dosen && $user->dosen !== null) {
            return $pendataan->dosen_pembimbing_id === $user->dosen->id
                || $pendataan->dosen_kunjung_id === $user->dosen->id;
        }

        return false;
    }

    public function generate(User $user, PendataanMagang $pendataan): bool
    {
        if ($user->role === null || ! $user->role->hasPermission('generate-draft-sk')) {
            return false;
        }

        return in_array($pendataan->status_pendataan, [
            StatusPendataan::Terverifikasi,
            StatusPendataan::Selesai,
        ], true);
    }

    public function approve(User $user, DokumenSk $dokumenSk): bool
    {
        return $user->role === UserRole::Kaprodi
            && $user->role->hasPermission('approve-sk');
    }
}
