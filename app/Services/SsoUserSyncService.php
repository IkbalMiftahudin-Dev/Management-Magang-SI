<?php

namespace App\Services;

use App\Data\SsoUserClaims;
use App\Enums\UserRole;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\User;

class SsoUserSyncService
{
    /**
     * Synchronize or provision a local User account and linked academic profile from verified JWT claims.
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
     * Synchronize a local User account and linked academic profile from a live /api/sso/userinfo payload.
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

        $this->syncAcademicProfile($user);

        return $user;
    }

    /**
     * Automatically link or provision Mahasiswa / Dosen profiles on SSO login.
     */
    private function syncAcademicProfile(User $user): void
    {
        if ($user->role === UserRole::Mahasiswa) {
            $mahasiswa = Mahasiswa::query()->where('user_id', $user->id)->first();

            if ($mahasiswa === null && $user->identifier_number !== null) {
                $mahasiswa = Mahasiswa::query()
                    ->whereNull('user_id')
                    ->where('nim', $user->identifier_number)
                    ->first();
            }

            if ($mahasiswa === null) {
                $mahasiswa = new Mahasiswa;
            }

            $mahasiswa->forceFill([
                'user_id' => $user->id,
                'nim' => $user->identifier_number ?? $mahasiswa->nim,
                'nama' => $user->name,
                'prodi' => $mahasiswa->prodi ?: 'Rekayasa Perangkat Lunak',
            ])->save();

            return;
        }

        if (in_array($user->role, [UserRole::Dosen, UserRole::Kaprodi], true)) {
            $dosen = Dosen::query()->where('user_id', $user->id)->first();

            if ($dosen === null && $user->identifier_number !== null) {
                $dosen = Dosen::query()
                    ->whereNull('user_id')
                    ->where('nip', $user->identifier_number)
                    ->first();
            }

            if ($dosen === null && $user->email !== '') {
                $dosen = Dosen::query()
                    ->whereNull('user_id')
                    ->where('email', $user->email)
                    ->first();
            }

            if ($dosen === null) {
                $dosen = new Dosen;
            }

            $dosen->forceFill([
                'user_id' => $user->id,
                'nip' => $user->identifier_number ?? $dosen->nip,
                'nama' => $user->name,
                'email' => $user->email,
            ])->save();
        }
    }
}
