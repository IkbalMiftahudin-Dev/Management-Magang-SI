<?php

namespace App\Http\Requests\Lowongan;

use App\Enums\StatusLowongan;
use App\Enums\SumberLowongan;
use App\Models\LowonganMagang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLowonganRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var LowonganMagang|null $lowongan */
        $lowongan = $this->route('lowongan');

        return $lowongan !== null && ($this->user()?->can('update', $lowongan) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'judul_lowongan' => ['sometimes', 'required', 'string', 'max:180'],
            'nama_instansi' => ['sometimes', 'required', 'string', 'max:150'],
            'posisi_bidang' => ['sometimes', 'required', 'string', 'max:120'],
            'lokasi_mode' => ['sometimes', 'required', 'string', 'max:120'],
            'sumber_lowongan' => ['sometimes', 'required', Rule::enum(SumberLowongan::class)],
            'kontak' => ['sometimes', 'required', 'string', 'max:150'],
            'deskripsi' => ['sometimes', 'required', 'string', 'max:5000'],
            'persyaratan' => ['nullable', 'string', 'max:5000'],
            'batas_waktu' => ['nullable', 'date'],
            'status' => ['sometimes', Rule::enum(StatusLowongan::class)],
            'dosen_penanggung_jawab_id' => ['nullable', 'integer', 'exists:dosens,id'],
        ];
    }
}
