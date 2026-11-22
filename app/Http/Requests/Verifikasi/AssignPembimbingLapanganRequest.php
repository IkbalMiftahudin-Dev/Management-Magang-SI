<?php

namespace App\Http\Requests\Verifikasi;

use App\Models\PendataanMagang;
use Illuminate\Foundation\Http\FormRequest;

class AssignPembimbingLapanganRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PendataanMagang|null $pendataan */
        $pendataan = $this->route('pendataan');

        return $pendataan !== null && ($this->user()?->can('plotPembimbingLapangan', $pendataan) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dosen_kunjung_id' => ['required', 'integer', 'exists:dosens,id'],
        ];
    }
}
