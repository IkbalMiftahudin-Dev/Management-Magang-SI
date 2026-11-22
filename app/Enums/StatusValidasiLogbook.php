<?php

namespace App\Enums;

enum StatusValidasiLogbook: string
{
    case Menunggu = 'menunggu';
    case Tervalidasi = 'tervalidasi';
    case Revisi = 'revisi';

    public function label(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu validasi',
            self::Tervalidasi => 'Tervalidasi',
            self::Revisi => 'Perlu revisi',
        };
    }
}
