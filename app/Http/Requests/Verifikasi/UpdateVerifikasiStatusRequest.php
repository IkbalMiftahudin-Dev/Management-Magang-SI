<?php

namespace App\Http\Requests\Verifikasi;

use App\Enums\StatusPendataan;
use App\Models\PendataanMagang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVerifikasiStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PendataanMagang|null $pendataan */
        $pendataan = $this->route('pendataan');

        return $pendataan !== null && ($this->user()?->can('verify', $pendataan) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([
                    StatusPendataan::Terverifikasi->value,
                    StatusPendataan::PerluRevisi->value,
                ]),
            ],
            'catatan_verifikasi' => [
                'nullable',
                'string',
                'max:1000',
                'required_if:status,'.StatusPendataan::PerluRevisi->value,
            ],
        ];
    }
}
