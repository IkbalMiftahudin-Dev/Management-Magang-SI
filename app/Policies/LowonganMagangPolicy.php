<?php

namespace App\Policies;

use App\Enums\StatusLowongan;
use App\Enums\UserRole;
use App\Models\LowonganMagang;
use App\Models\User;

class LowonganMagangPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role !== null && $user->role->hasPermission('view-lowongan');
    }

    public function view(User $user, LowonganMagang $lowongan): bool
    {
        if ($user->role === null || ! $user->role->hasPermission('view-lowongan')) {
            return false;
        }

        if ($user->role->hasPermission('manage-lowongan')) {
            return true;
        }

        return $lowongan->status === StatusLowongan::Aktif;
    }

    public function create(User $user): bool
    {
        return $user->role !== null && $user->role->hasPermission('manage-lowongan');
    }

    public function update(User $user, LowonganMagang $lowongan): bool
    {
        return $user->role !== null && $user->role->hasPermission('manage-lowongan');
    }

    public function delete(User $user, LowonganMagang $lowongan): bool
    {
        return $user->role !== null && $user->role->hasPermission('manage-lowongan');
    }

    public function manage(User $user): bool
    {
        return in_array($user->role, [UserRole::StaffKaprodi, UserRole::Kaprodi, UserRole::Admin], true);
    }
}
