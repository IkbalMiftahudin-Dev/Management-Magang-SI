<?php

namespace App\Enums;

enum StatusPengesahanSk: string
{
    case Draft = 'draft';
    case Disahkan = 'disahkan';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf SK',
            self::Disahkan => 'Disahkan',
        };
    }
}
