<?php

namespace App\Policies;

use App\Enums\StatusBerkasAkhir;
use App\Enums\UserRole;
use App\Models\KonversiMataKuliah;
use App\Models\User;

class KonversiMataKuliahPolicy
{
    public function view(User $user, KonversiMataKuliah $konversi): bool
    {
        $pendataan = $konversi->pendataanMagang;

        if ($pendataan === null) {
            return false;
        }

        return app(PendataanMagangPolicy::class)->view($user, $pendataan);
    }

    public function grade(User $user, KonversiMataKuliah $konversi): bool
    {
        if ($user->role !== UserRole::Dosen || $user->dosen === null) {
            return false;
        }

        if ($konversi->dosen_penilai_id !== $user->dosen->id || $konversi->is_locked) {
            return false;
        }

        $berkas = $konversi->pendataanMagang?->berkasAkhir;

        return $berkas !== null && in_array($berkas->status_kelengkapan, [
            StatusBerkasAkhir::SiapDinilai,
            StatusBerkasAkhir::Terverifikasi,
        ], true);
    }

    public function publish(User $user, KonversiMataKuliah $konversi): bool
    {
        return $this->grade($user, $konversi);
    }
}
