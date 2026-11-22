<?php

namespace App\Http\Requests\Lowongan;

use App\Enums\StatusLowongan;
use App\Enums\SumberLowongan;
use App\Models\LowonganMagang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLowonganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', LowonganMagang::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'judul_lowongan' => ['required', 'string', 'max:180'],
            'nama_instansi' => ['required', 'string', 'max:150'],
            'posisi_bidang' => ['required', 'string', 'max:120'],
            'lokasi_mode' => ['required', 'string', 'max:120'],
            'sumber_lowongan' => ['required', Rule::enum(SumberLowongan::class)],
            'kontak' => ['required', 'string', 'max:150'],
            'deskripsi' => ['required', 'string', 'max:5000'],
            'persyaratan' => ['nullable', 'string', 'max:5000'],
            'batas_waktu' => ['nullable', 'date'],
            'status' => ['sometimes', Rule::enum(StatusLowongan::class)],
            'dosen_penanggung_jawab_id' => ['nullable', 'integer', 'exists:dosens,id'],
        ];
    }
}
