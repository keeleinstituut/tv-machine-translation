<?php

namespace App\Http\Requests\API;

use App\Enums\ProviderName;
use App\Enums\TranslationJobStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTranslationJobsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statuses  = array_column(TranslationJobStatus::cases(), 'value');
        $providers = array_column(ProviderName::cases(), 'value');

        return [
            'status'          => ['sometimes', 'string', Rule::in($statuses)],
            'type'            => ['sometimes', 'string', Rule::in(['text', 'file'])],
            'provider'        => ['sometimes', 'string', Rule::in($providers)],
            'source_language' => ['sometimes', 'string', 'min:2', 'max:10'],
            'target_language' => ['sometimes', 'string', 'min:2', 'max:10'],
            'per_page'        => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
