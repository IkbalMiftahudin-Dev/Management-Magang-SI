<?php

namespace App\Http\Requests\Logbook;

use App\Enums\TipeLogbook;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLogbookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role?->hasPermission('fill-logbook') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tipe_pengisian' => ['required', Rule::enum(TipeLogbook::class)],
            'tanggal' => ['required', 'date'],
            'minggu_ke' => ['required', 'integer', 'min:1', 'max:52'],
            'deskripsi_kegiatan' => ['required', 'string', 'min:10', 'max:5000'],
            'foto_bukti' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];
    }
}
