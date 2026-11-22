<?php

namespace App\Http\Requests\Penilaian;

use App\Models\KonversiMataKuliah;
use Illuminate\Foundation\Http\FormRequest;

class SavePenilaianRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var KonversiMataKuliah|null $konversi */
        $konversi = $this->route('konversi');

        return $konversi !== null && ($this->user()?->can('grade', $konversi) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nilai_instansi' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'catatan_dosen' => ['nullable', 'string', 'max:2000'],
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.rubrik_komponen_id' => ['required', 'integer', 'distinct', 'exists:rubrik_komponens,id'],
            'scores.*.nilai' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
