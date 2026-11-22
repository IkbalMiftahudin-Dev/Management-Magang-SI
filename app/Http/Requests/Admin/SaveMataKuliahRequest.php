<?php

namespace App\Http\Requests\Admin;

use App\Models\MataKuliah;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveMataKuliahRequest extends FormRequest
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
        /** @var MataKuliah|null $mataKuliah */
        $mataKuliah = $this->route('mataKuliah');

        return [
            'kode_mk' => [
                'required',
                'string',
                'max:20',
                Rule::unique('mata_kuliahs', 'kode_mk')->ignore($mataKuliah?->id),
            ],
            'nama_mk' => ['required', 'string', 'max:150'],
            'sks' => ['required', 'integer', 'min:1', 'max:20'],
            'dosen_pengampu_id' => ['nullable', 'integer', 'exists:dosens,id'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
            'rubrik_komponens' => ['required', 'array', 'min:1'],
            'rubrik_komponens.*.nama_komponen' => ['required', 'string', 'max:150'],
            'rubrik_komponens.*.deskripsi' => ['nullable', 'string', 'max:255'],
            'rubrik_komponens.*.bobot_persen' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}
