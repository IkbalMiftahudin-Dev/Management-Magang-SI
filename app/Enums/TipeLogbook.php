<?php

namespace App\Enums;

enum TipeLogbook: string
{
    case Mingguan = 'mingguan';
    case RekapBulanan = 'rekap_bulanan';

    public function label(): string
    {
        return match ($this) {
            self::Mingguan => 'Mingguan',
            self::RekapBulanan => 'Rekap Bulanan',
        };
    }
}
