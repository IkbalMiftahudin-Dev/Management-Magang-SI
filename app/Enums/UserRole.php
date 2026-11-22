<?php

namespace App\Enums;

enum UserRole: string
{
    case Mahasiswa = 'Mahasiswa';
    case Dosen = 'Dosen';
    case StaffKaprodi = 'Staff_Kaprodi';
    case Kaprodi = 'Kaprodi';
    case Admin = 'Admin';

    /**
     * Human-readable Indonesian role label for UI display.
     */
    public function label(): string
    {
        return match ($this) {
            self::Mahasiswa => 'Mahasiswa',
            self::Dosen => 'Dosen',
            self::StaffKaprodi => 'Staff Prodi',
            self::Kaprodi => 'Kaprodi',
            self::Admin => 'Admin',
        };
    }

    /**
     * Prefix label for the identifier number (NIM or NIP).
     */
    public function identifierLabel(): string
    {
        return match ($this) {
            self::Mahasiswa => 'NIM',
            default => 'NIP',
        };
    }

    /**
     * Named landing route for each role after SSO authentication.
     */
    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Mahasiswa => 'mahasiswa.beranda',
            self::Dosen => 'dosen.penilaian.index',
            self::StaffKaprodi => 'staff.verifikasi.index',
            self::Kaprodi => 'kaprodi.verifikasi.index',
            self::Admin => 'admin.dashboard',
        };
    }

    /**
     * Explicit ability list mapped per role according to Modul 5 RBAC matrix.
     *
     * @return list<string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Mahasiswa => [
                'view-lowongan',
                'submit-pendataan',
                'fill-logbook',
                'submit-berkas-akhir',
                'download-own-sk',
                'view-own-grades',
            ],
            self::Dosen => [
                'view-lowongan',
                'validate-logbook',
                'view-bimbingan-berkas',
                'grade-konversi',
                'download-bimbingan-sk',
            ],
            self::StaffKaprodi => [
                'view-lowongan',
                'manage-lowongan',
                'verify-pendataan',
                'plot-pembimbing-lapangan',
                'generate-draft-sk',
                'view-all-pendataan',
                'download-all-sk',
            ],
            self::Kaprodi => [
                'view-lowongan',
                'manage-lowongan',
                'verify-pendataan',
                'plot-pembimbing-lapangan',
                'generate-draft-sk',
                'approve-sk',
                'view-all-pendataan',
                'view-rekap-penilaian',
                'download-all-sk',
            ],
            self::Admin => [
                'view-lowongan',
                'manage-lowongan',
                'manage-master-data',
                'view-all-pendataan',
                'view-rekap-penilaian',
                'download-all-sk',
                'view-audit-logs',
            ],
        };
    }

    /**
     * Determine whether this role grants the given ability.
     */
    public function hasPermission(string $ability): bool
    {
        return in_array($ability, $this->permissions(), true);
    }

    /**
     * Resolve a UserRole from either its exact SSO value or enum case name.
     */
    public static function fromInput(self|string $role): ?self
    {
        if ($role instanceof self) {
            return $role;
        }

        return self::tryFrom(trim($role));
    }
}
