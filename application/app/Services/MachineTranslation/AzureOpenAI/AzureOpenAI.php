<?php

namespace App\Services\MachineTranslation\AzureOpenAI;

use App\Enums\ProviderName;
use App\Enums\TranslationJobStatus;
use App\Models\TranslationJob;
use App\Services\MachineTranslation\Contracts\MachineTranslationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class AzureOpenAI implements MachineTranslationService
{
    private AzureOpenAIApiClient $apiClient;

    public function __construct()
    {
        $this->apiClient = new AzureOpenAIApiClient();
    }

    public function getOptions(): array
    {
        $templates = config('machine-translation.azure_openai.prompt_templates', []);

        return [
            'prompt_templates' => collect($templates)
                ->map(fn ($t, $key) => ['value' => $key, 'label' => $t['label']])
                ->values()
                ->all(),
        ];
    }

    public function submitTextTranslation(
        string $text,
        string $sourceLanguage,
        string $targetLanguage,
        array  $options = [],
        string $institutionUserId = ''
    ): TranslationJob {
        $templateKey = $options['template'] ?? 'default';
        $templates   = config('machine-translation.azure_openai.prompt_templates', []);
        $template    = $templates[$templateKey] ?? $templates['default'];

        $placeholders = ['{source_language}', '{target_language}', '{text}'];
        $values       = [$sourceLanguage, $targetLanguage, $text];

        $systemPrompt = str_replace($placeholders, $values, $template['system']);
        $userPrompt   = str_replace($placeholders, $values, $template['user']);

        $translatedText = $this->apiClient->chatCompletion($systemPrompt, $userPrompt);

        return TranslationJob::create([
            'id'                  => Str::uuid()->toString(),
            'provider'            => ProviderName::AzureOpenAI->value,
            'type'                => 'text',
            'status'              => TranslationJobStatus::Completed->value,
            'source_language'     => $sourceLanguage,
            'target_language'     => $targetLanguage,
            'options'             => $options,
            'input_text'          => $text,
            'output_text'         => $translatedText,
            'institution_user_id' => $institutionUserId,
        ]);
    }

    public function supportsFileTranslation(): bool
    {
        return false;
    }

    public function submitFileTranslation(
        UploadedFile $_file,
        string       $_sourceLanguage,
        string       $_targetLanguage,
        array        $_options = [],
        string       $_institutionUserId = ''
    ): TranslationJob {
        throw new \BadMethodCallException('Azure OpenAI does not support file translation.');
    }

    public function pollTranslationStatus(TranslationJob $_job): TranslationJob
    {
        throw new \BadMethodCallException('Azure OpenAI does not support file translation.');
    }

    public function getTranslatedFileContent(TranslationJob $_job): array
    {
        throw new \BadMethodCallException('Azure OpenAI does not support file translation.');
    }
}
