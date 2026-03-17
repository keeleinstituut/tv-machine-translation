<?php

namespace App\Enums;

enum ProviderName: string
{
    case ETranslation = 'etranslation';
    case AzureOpenAI  = 'azure_openai';

    public function label(): string
    {
        return match ($this) {
            self::ETranslation => 'eTranslation',
            self::AzureOpenAI  => 'Azure OpenAI (Copilot)',
        };
    }
}
