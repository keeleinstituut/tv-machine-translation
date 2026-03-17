<?php

namespace App\Services\MachineTranslation;

use App\Enums\ProviderName;
use App\Services\MachineTranslation\AzureOpenAI\AzureOpenAI;
use App\Services\MachineTranslation\Contracts\MachineTranslationService;
use App\Services\MachineTranslation\ETranslation\ETranslation;
use RuntimeException;

readonly class MachineTranslationPickerService
{
    public function pick(string $providerName): MachineTranslationService
    {
        return match ($providerName) {
            ProviderName::ETranslation->value => new ETranslation(),
            ProviderName::AzureOpenAI->value  => new AzureOpenAI(),
            default => throw new RuntimeException("No machine translation provider with name \"$providerName\" exists"),
        };
    }
}
