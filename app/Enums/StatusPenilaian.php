<?php

namespace App\Enums;

enum StatusPenilaian: string
{
    case BelumDinilai = 'belum_dinilai';
    case Draf = 'draf';
    case Dipublikasikan = 'dipublikasikan';

    public function label(): string
    {
        return match ($this) {
            self::BelumDinilai => 'Belum dinilai',
            self::Draf => 'Draf nilai',
            self::Dipublikasikan => 'Dipublikasikan',
        };
    }
}
