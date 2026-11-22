<?php

namespace App\Http\Requests\BerkasAkhir;

use Illuminate\Foundation\Http\FormRequest;

class UploadBerkasSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role?->hasPermission('submit-berkas-akhir') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }
}
