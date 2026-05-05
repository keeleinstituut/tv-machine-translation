<?php

namespace App\Services\MachineTranslation\AzureOpenAI;

use App\Enums\ProviderName;
use App\Enums\TranslationJobStatus;
use App\Exceptions\TranslationFailedException;
use App\Models\Setting;
use App\Models\TranslationJob;
use App\Services\MachineTranslation\Contracts\MachineTranslationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class AzureOpenAI implements MachineTranslationService
{
    private ?AzureOpenAIApiClient $apiClient = null;
    private string $institutionId;

    public function __construct(string $institutionId = '')
    {
        $this->institutionId = $institutionId;
    }

    private function resolveApiClient(): AzureOpenAIApiClient
    {
        if ($this->apiClient !== null) {
            return $this->apiClient;
        }

        if ($this->institutionId !== '') {
            $settings = Setting::where('institution_id', $this->institutionId)
                ->where('key', 'LIKE', 'azure_openai_%')
                ->pluck('value', 'key');

            if ($settings->isEmpty()) {
                throw new TranslationFailedException(
                    'Azure OpenAI settings are not configured for this institution.'
                );
            }

            $config = [
                'endpoint'       => $settings->get('azure_openai_endpoint'),
                'api_key'        => $settings->get('azure_openai_api_key'),
                'tenant_id'      => $settings->get('azure_openai_tenant_id'),
                'application_id' => $settings->get('azure_openai_application_id'),
                'client_secret'  => $settings->get('azure_openai_client_secret'),
                'deployment'     => $settings->get('azure_openai_deployment'),
            ];
        } else {
            $config = config('machine-translation.azure_openai');
        }

        $this->apiClient = new AzureOpenAIApiClient($config, $this->institutionId);

        return $this->apiClient;
    }

    public function getOptions(): array
    {
        $allTemplateKeys       = array_keys(config('machine-translation.azure_openai.prompt_templates', []));
        $crossLangTemplates    = array_values(array_filter($allTemplateKeys, fn($k) => $k !== 'translation_edited'));
        $sameLanguageTemplates = ['translation_edited'];
        $languages             = config('machine-translation.azure_openai.languages', []);

        $combinations = [];
        foreach ($languages as $source) {
            foreach ($languages as $target) {
                $combinations[$source][$target] = $source === $target
                    ? $sameLanguageTemplates
                    : $crossLangTemplates;
            }
            ksort($combinations[$source]);
        }
        ksort($combinations);

        return ['language_combinations' => $combinations];
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

        $translatedText = $this->resolveApiClient()->chatCompletion($systemPrompt, $userPrompt);

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

    public function isConfiguredForInstitution(): bool
    {
        if ($this->institutionId === '') {
            return true;
        }

        $azureSettings = Setting::getModel()
            ->where('institution_id', $this->institutionId)
            ->whereIn('key', [
                'azure_openai_endpoint',
                'azure_openai_tenant_id',
                'azure_openai_application_id',
                'azure_openai_deployment',
                'azure_openai_api_key',
                'azure_openai_client_secret',
            ])
            ->pluck('value', 'key');


        $missingAPIKey = blank($azureSettings->get('azure_openai_api_key'));
        $missingBearerKeys = collect($azureSettings)
                                ->forget('azure_openai_api_key')
                                ->map(blank(...))
                                ->reduce(fn ($acc, $bool) => $acc || $bool, false);

        return !$missingAPIKey || !$missingBearerKeys;
    }

    public function isShowUsageConfirmationEnabled(): bool {
        if ($this->institutionId === '') {
            return false;
        }

        return (bool) Setting::where('institution_id', $this->institutionId)
            ->where('key', 'azure_openai_show_confirmation')
            ->first()?->value;

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
