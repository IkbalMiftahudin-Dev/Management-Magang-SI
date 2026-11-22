<?php

namespace App\Enums;

enum TingkatKesesuaian: string
{
    case SangatSesuai = 'sangat_sesuai';
    case Sesuai = 'sesuai';
    case KurangSesuai = 'kurang_sesuai';

    public function label(): string
    {
        return match ($this) {
            self::SangatSesuai => 'Sangat sesuai',
            self::Sesuai => 'Sesuai',
            self::KurangSesuai => 'Kurang sesuai',
        };
    }
}
