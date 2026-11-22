<?php

namespace App\Enums;

enum StatusBerkasAkhir: string
{
    case BelumLengkap = 'belum_lengkap';
    case SiapDinilai = 'siap_dinilai';
    case Terverifikasi = 'terverifikasi';

    public function label(): string
    {
        return match ($this) {
            self::BelumLengkap => 'Belum lengkap',
            self::SiapDinilai => 'Lengkap - Siap Dinilai',
            self::Terverifikasi => 'Terverifikasi',
        };
    }
}
