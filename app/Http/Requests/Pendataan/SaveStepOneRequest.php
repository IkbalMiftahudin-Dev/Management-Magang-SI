<?php

namespace App\Http\Requests\Pendataan;

use App\Models\PendataanMagang;
use Illuminate\Foundation\Http\FormRequest;

class SaveStepOneRequest extends FormRequest
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
            'nama_instansi' => ['required', 'string', 'max:150'],
            'posisi' => ['required', 'string', 'max:150'],
            'alamat_instansi' => ['required', 'string', 'max:1000'],
            'kontak_instansi' => ['required', 'string', 'max:150'],
            'semester_akademik' => ['nullable', 'string', 'max:40'],
        ];
    }
}
