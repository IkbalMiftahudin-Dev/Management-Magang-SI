<?php

namespace App\Enums;

enum JenisSk: string
{
    case SkMagang = 'sk_magang';
    case SkKunjung = 'sk_kunjung';

    public function label(): string
    {
        return match ($this) {
            self::SkMagang => 'SK Magang & Pembimbing',
            self::SkKunjung => 'SK Pembimbing Lapangan / Kunjung',
        };
    }
}
