<?php

namespace App\Enums;

enum StatusPendataan: string
{
    case Draft = 'draft';
    case MenungguVerifikasi = 'menunggu_verifikasi';
    case PerluRevisi = 'perlu_revisi';
    case Terverifikasi = 'terverifikasi';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf belum dikirim',
            self::MenungguVerifikasi => 'Menunggu',
            self::PerluRevisi => 'Perlu revisi',
            self::Terverifikasi => 'Terverifikasi',
            self::Selesai => 'Selesai',
        };
    }

    public function isEditableByStudent(): bool
    {
        return in_array($this, [self::Draft, self::PerluRevisi], true);
    }
}
