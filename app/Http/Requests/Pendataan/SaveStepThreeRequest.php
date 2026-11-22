<?php

namespace App\Http\Requests\Pendataan;

use App\Models\PendataanMagang;
use Illuminate\Foundation\Http\FormRequest;

class SaveStepThreeRequest extends FormRequest
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
            'mata_kuliah_ids' => ['required', 'array', 'min:1'],
            'mata_kuliah_ids.*' => ['required', 'integer', 'distinct', 'exists:mata_kuliahs,id'],
        ];
    }
}
