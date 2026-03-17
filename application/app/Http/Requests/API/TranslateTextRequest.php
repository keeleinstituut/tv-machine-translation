<?php

namespace App\Http\Requests\API;

use App\Enums\ProviderName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TranslateTextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth handled by middleware
    }

    public function rules(): array
    {
        $providers  = array_column(ProviderName::cases(), 'value');
        $domains    = array_keys(config('machine-translation.etranslation.domains', []));
        $templates  = array_keys(config('machine-translation.azure_openai.prompt_templates', []));

        return [
            'provider'         => ['required', 'string', Rule::in($providers)],
            'text'             => ['required', 'string', 'min:1', 'max:10000'],
            'source_language'  => ['required', 'string', 'min:2', 'max:10'],
            'target_language'  => ['required', 'string', 'min:2', 'max:10'],
            'options'          => ['sometimes', 'array'],
            'options.domain'   => ['sometimes', 'string', Rule::in($domains)],
            'options.template' => ['sometimes', 'string', Rule::in($templates)],
        ];
    }
}
