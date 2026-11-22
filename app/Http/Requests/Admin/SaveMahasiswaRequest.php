<?php

namespace App\Http\Requests\Admin;

use App\Models\Mahasiswa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveMahasiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-master-data') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Mahasiswa|null $mahasiswa */
        $mahasiswa = $this->route('mahasiswa');

        return [
            'nim' => ['required', 'string', 'max:30', Rule::unique('mahasiswas', 'nim')->ignore($mahasiswa?->id)],
            'nama' => ['required', 'string', 'max:150'],
            'prodi' => ['required', 'string', 'max:100'],
            'angkatan' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'sks_lulus' => ['nullable', 'integer', 'min:0', 'max:200'],
        ];
    }
}
