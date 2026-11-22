<?php

namespace App\Enums;

enum SumberLowongan: string
{
    case MitraKampus = 'mitra_kampus';
    case TawaranDosen = 'tawaran_dosen';

    public function label(): string
    {
        return match ($this) {
            self::MitraKampus => 'Mitra Kampus',
            self::TawaranDosen => 'Tawaran Dosen',
        };
    }

    public function cardBadgeLabel(): string
    {
        return match ($this) {
            self::MitraKampus => 'Mitra Kampus',
            self::TawaranDosen => 'Binaan Dosen',
        };
    }
}
