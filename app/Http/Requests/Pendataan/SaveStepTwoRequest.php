<?php

namespace App\Http\Requests\Pendataan;

use App\Enums\JenisProgramMagang;
use App\Enums\ModeKegiatan;
use App\Models\PendataanMagang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveStepTwoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PendataanMagang::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'jenis_program' => ['required', Rule::enum(JenisProgramMagang::class)],
            'dosen_pembimbing_id' => ['required', 'integer', 'exists:dosens,id'],
            'mode_kegiatan' => ['required', Rule::enum(ModeKegiatan::class)],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after:tanggal_mulai'],
            'loa' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}
