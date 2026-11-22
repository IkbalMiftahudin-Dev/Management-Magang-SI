<?php

namespace App\Policies;

use App\Enums\StatusPendataan;
use App\Enums\StatusValidasiLogbook;
use App\Enums\UserRole;
use App\Models\Logbook;
use App\Models\PendataanMagang;
use App\Models\User;

class LogbookPolicy
{
    public function view(User $user, Logbook $logbook): bool
    {
        $pendataan = $logbook->pendataanMagang;

        if ($pendataan === null) {
            return false;
        }

        return app(PendataanMagangPolicy::class)->view($user, $pendataan);
    }

    public function create(User $user, PendataanMagang $pendataan): bool
    {
        if ($user->role !== UserRole::Mahasiswa || $user->mahasiswa === null) {
            return false;
        }

        return $pendataan->mahasiswa_id === $user->mahasiswa->id
            && $pendataan->status_pendataan === StatusPendataan::Terverifikasi;
    }

    public function update(User $user, Logbook $logbook): bool
    {
        $pendataan = $logbook->pendataanMagang;

        if ($pendataan === null || ! $this->create($user, $pendataan)) {
            return false;
        }

        return $logbook->status_validasi !== StatusValidasiLogbook::Tervalidasi;
    }

    public function delete(User $user, Logbook $logbook): bool
    {
        return $this->update($user, $logbook);
    }

    public function validate(User $user, Logbook $logbook): bool
    {
        if ($user->role !== UserRole::Dosen || $user->dosen === null) {
            return false;
        }

        $pendataan = $logbook->pendataanMagang;

        return $pendataan !== null
            && $pendataan->dosen_pembimbing_id === $user->dosen->id;
    }
}
