<?php

namespace App\Services;

use App\Data\SsoUserClaims;
use App\Enums\UserRole;
use App\Models\User;

class SsoUserSyncService
{
    /**
     * Synchronize or provision a local User account from verified JWT claims (keyed by sso_sub).
     */
    public function syncFromClaims(SsoUserClaims $claims): User
    {
        return $this->syncAttributes(
            sub: $claims->sub,
            name: $claims->name,
            email: $claims->email,
            nip: $claims->nip,
            role: $claims->role,
        );
    }

    /**
     * Synchronize a local User account from a live /api/sso/userinfo payload.
     *
     * @param  array{sub: string, name: string, email: string, nip: ?string, role: UserRole}  $userInfo
     */
    public function syncFromUserInfo(array $userInfo): User
    {
        return $this->syncAttributes(
            sub: $userInfo['sub'],
            name: $userInfo['name'],
            email: $userInfo['email'],
            nip: $userInfo['nip'],
            role: $userInfo['role'],
        );
    }

    private function syncAttributes(
        string $sub,
        string $name,
        string $email,
        ?string $nip,
        UserRole $role,
    ): User {
        $user = User::query()->where('sso_sub', $sub)->first();

        if ($user === null && $email !== '') {
            $user = User::query()
                ->whereNull('sso_sub')
                ->where('email', $email)
                ->first();
        }

        if ($user === null) {
            $user = new User;
        }

        $user->forceFill([
            'sso_sub' => $sub,
            'name' => $name,
            'email' => $email !== '' ? $email : $sub.'@sso.local',
            'identifier_number' => $nip,
            'role' => $role,
            'email_verified_at' => $user->email_verified_at ?? now(),
            'last_sso_sync_at' => now(),
        ]);

        $user->save();

        return $user;
    }
}
