<?php

return [

    'etranslation' => [
        'base_url'                     => env('ETRANSLATION_API_URL', 'https://webgate.ec.europa.eu/etranslation/si/translate'),
        'username'                     => env('ETRANSLATION_USERNAME', ''),
        'password'                     => env('ETRANSLATION_PASSWORD', ''),
        'application_name'             => env('ETRANSLATION_APP_NAME', 'tolkevarav'),
        'callback_url'                 => env('ETRANSLATION_CALLBACK_URL', ''),
        'callback_basic_auth_username' => env('ETRANSLATION_CALLBACK_BASIC_AUTH_USERNAME', ''),
        'callback_basic_auth_password' => env('ETRANSLATION_CALLBACK_BASIC_AUTH_PASSWORD', ''),

        'timeout' => (int) env('ETRANSLATION_TIMEOUT', 30),
        'domains' => ['GEN', 'SPD', 'ECB', 'IPO', 'QE', 'ECJ'],
    ],

    'azure_openai' => [
        'endpoint'       => env('AZURE_OPENAI_ENDPOINT', ''),
        'api_key'        => env('AZURE_OPENAI_API_KEY', ''),
        'tenant_id'      => env('AZURE_OPENAI_TENANT_ID', ''),
        'application_id' => env('AZURE_OPENAI_APPLICATION_ID', ''),
        'client_secret'  => env('AZURE_OPENAI_CLIENT_SECRET', ''),
        'deployment'     => env('AZURE_OPENAI_DEPLOYMENT', 'gpt-4o'),
        'api_version'    => env('AZURE_OPENAI_API_VERSION', '2024-10-21'),
        'timeout'        => (int) env('AZURE_OPENAI_TIMEOUT', 60),

        'languages' => [
            'ar', 'bg', 'cs', 'da', 'de', 'el', 'en', 'es', 'et', 'fi', 'fr', 'ga',
            'hr', 'hu', 'is', 'it', 'ja', 'lt', 'lv', 'mt', 'nb', 'nl', 'nn', 'pl',
            'pt', 'ro', 'ru', 'sk', 'sl', 'sv', 'tr', 'uk', 'zh', 'zt',
        ],

        'prompt_templates' => [
            'default' => [
                'system' => 'You are a professional translator. Translate the provided text from {source_language} to {target_language}. Output only the translated text, nothing else.',
                'user'   => '{text}',
            ],
            'formal' => [
                'system' => 'You are a professional translator specializing in formal documents. Translate from {source_language} to {target_language} using a formal register. Output only the translated text, nothing else.',
                'user'   => '{text}',
            ],
            'technical' => [
                'system' => 'You are a technical translator. Translate from {source_language} to {target_language} while preserving all technical terminology accurately. Output only the translated text, nothing else.',
                'user'   => '{text}',
            ],
        ],
    ],

];
