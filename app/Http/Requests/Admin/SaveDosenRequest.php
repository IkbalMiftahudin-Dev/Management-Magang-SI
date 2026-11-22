<?php

namespace App\Http\Requests\Admin;

use App\Models\Dosen;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveDosenRequest extends FormRequest
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
        /** @var Dosen|null $dosen */
        $dosen = $this->route('dosen');

        return [
            'nip' => ['required', 'string', 'max:50', Rule::unique('dosens', 'nip')->ignore($dosen?->id)],
            'nama' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'bidang_kepakaran' => ['nullable', 'string', 'max:255'],
        ];
    }
}
