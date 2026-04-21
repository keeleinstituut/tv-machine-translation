<?php

namespace App\Http\Requests\API;

use App\Enums\ProviderName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TranslateFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth handled by middleware
    }

    public function rules(): array
    {
        $domains = config('machine-translation.etranslation.domains', []);

        return [
            'provider'        => ['required', 'string', Rule::in([ProviderName::ETranslation->value])],
            'file'            => ['required', 'file', 'max:51200'], // 50 MB; eTranslation validates format
            'source_language' => ['required', 'string', 'min:2', 'max:10'],
            'target_language' => ['required', 'string', 'min:2', 'max:10'],
            'options'         => ['sometimes', 'array'],
            'options.domain'  => ['sometimes', 'string', Rule::in($domains)],
        ];
    }
}
