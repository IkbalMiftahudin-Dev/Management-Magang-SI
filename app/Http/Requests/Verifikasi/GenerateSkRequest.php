<?php

namespace App\Http\Requests\Verifikasi;

use App\Enums\JenisSk;
use App\Models\DokumenSk;
use App\Models\PendataanMagang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateSkRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PendataanMagang|null $pendataan */
        $pendataan = $this->route('pendataan');

        return $pendataan !== null && ($this->user()?->can('generate', [DokumenSk::class, $pendataan]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'jenis_sk' => ['required', Rule::enum(JenisSk::class)],
        ];
    }
}
