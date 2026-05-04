<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'endpoint'       => ['sometimes', 'nullable', 'string', 'url', 'max:500'],
            'api_key'        => ['sometimes', 'nullable', 'string', 'max:500'],
            'tenant_id'      => ['sometimes', 'nullable', 'string', 'max:255'],
            'application_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'client_secret'  => ['sometimes', 'nullable', 'string', 'max:500'],
            'deployment'        => ['sometimes', 'nullable', 'string', 'max:100'],
            'show_confirmation' => ['sometimes', 'boolean'],
        ];
    }
}
