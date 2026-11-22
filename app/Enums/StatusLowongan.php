<?php

namespace App\Enums;

enum StatusLowongan: string
{
    case Draft = 'draft';
    case Aktif = 'aktif';
    case Arsip = 'arsip';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Aktif => 'Aktif',
            self::Arsip => 'Arsip',
        };
    }
}
