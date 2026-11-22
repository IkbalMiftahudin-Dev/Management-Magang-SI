<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\PendataanMagang;
use App\Models\User;

class PendataanMagangPolicy
{
    public function view(User $user, PendataanMagang $pendataan): bool
    {
        if ($user->role === null) {
            return false;
        }

        if ($user->role->hasPermission('view-all-pendataan')) {
            return true;
        }

        if ($user->role === UserRole::Mahasiswa) {
            return $user->mahasiswa !== null && $pendataan->mahasiswa_id === $user->mahasiswa->id;
        }

        if ($user->role === UserRole::Dosen && $user->dosen !== null) {
            $dosenId = $user->dosen->id;

            if ($pendataan->dosen_pembimbing_id === $dosenId || $pendataan->dosen_kunjung_id === $dosenId) {
                return true;
            }

            return $pendataan->konversiMataKuliahs()
                ->where('dosen_penilai_id', $dosenId)
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Mahasiswa && $user->mahasiswa !== null;
    }

    public function update(User $user, PendataanMagang $pendataan): bool
    {
        if ($user->role !== UserRole::Mahasiswa || $user->mahasiswa === null) {
            return false;
        }

        return $pendataan->mahasiswa_id === $user->mahasiswa->id
            && $pendataan->status_pendataan->isEditableByStudent();
    }

    public function submit(User $user, PendataanMagang $pendataan): bool
    {
        return $this->update($user, $pendataan);
    }

    public function verify(User $user, PendataanMagang $pendataan): bool
    {
        return $user->role !== null && $user->role->hasPermission('verify-pendataan');
    }

    public function plotPembimbingLapangan(User $user, PendataanMagang $pendataan): bool
    {
        return $user->role !== null && $user->role->hasPermission('plot-pembimbing-lapangan');
    }
}
