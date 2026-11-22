<?php

namespace App\Policies;

use App\Enums\StatusPendataan;
use App\Enums\UserRole;
use App\Models\BerkasAkhir;
use App\Models\PendataanMagang;
use App\Models\User;

class BerkasAkhirPolicy
{
    public function view(User $user, BerkasAkhir $berkasAkhir): bool
    {
        $pendataan = $berkasAkhir->pendataanMagang;

        if ($pendataan === null) {
            return false;
        }

        return app(PendataanMagangPolicy::class)->view($user, $pendataan);
    }

    public function upload(User $user, PendataanMagang $pendataan): bool
    {
        if ($user->role !== UserRole::Mahasiswa || $user->mahasiswa === null) {
            return false;
        }

        return $pendataan->mahasiswa_id === $user->mahasiswa->id
            && $pendataan->status_pendataan === StatusPendataan::Terverifikasi;
    }

    public function submit(User $user, PendataanMagang $pendataan): bool
    {
        return $this->upload($user, $pendataan);
    }
}
