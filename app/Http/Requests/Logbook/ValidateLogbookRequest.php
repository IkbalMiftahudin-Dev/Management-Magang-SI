<?php

namespace App\Http\Requests\Logbook;

use App\Enums\StatusValidasiLogbook;
use App\Models\Logbook;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ValidateLogbookRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Logbook|null $logbook */
        $logbook = $this->route('logbook');

        return $logbook !== null && ($this->user()?->can('validate', $logbook) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status_validasi' => [
                'required',
                Rule::in([
                    StatusValidasiLogbook::Tervalidasi->value,
                    StatusValidasiLogbook::Revisi->value,
                ]),
            ],
            'catatan_dosen' => [
                'nullable',
                'string',
                'max:1000',
                'required_if:status_validasi,'.StatusValidasiLogbook::Revisi->value,
            ],
        ];
    }
}
