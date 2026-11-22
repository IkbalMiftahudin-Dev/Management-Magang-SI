<?php

namespace App\Http\Requests\Pendataan;

use App\Models\PendataanMagang;
use Illuminate\Foundation\Http\FormRequest;

class SubmitStepFourRequest extends FormRequest
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
            'pernyataan_disetujui' => ['required', 'accepted'],
        ];
    }
}
