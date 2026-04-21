<?php

namespace App\Services\MachineTranslation\AzureOpenAI;

use App\Exceptions\TranslationFailedException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AzureOpenAIApiClient
{
    private string $institutionId;
    private string $endpoint;
    private string $apiKey;
    private string $tenantId;
    private string $applicationId;
    private string $clientSecret;
    private string $deployment;
    private string $apiVersion;
    private int    $timeout;

    public function __construct(array $config, string $institutionId = '')
    {
        $this->institutionId = $institutionId;
        $this->endpoint      = $config['endpoint']      ?? '';
        $this->apiKey        = $config['api_key']        ?? '';
        $this->tenantId      = $config['tenant_id']      ?? '';
        $this->applicationId = $config['application_id'] ?? '';
        $this->clientSecret  = $config['client_secret']  ?? '';
        $this->deployment    = $config['deployment']     ?? config('machine-translation.azure_openai.deployment');
        $this->apiVersion    = config('machine-translation.azure_openai.api_version');
        $this->timeout       = (int) config('machine-translation.azure_openai.timeout');
    }

    /**
     * Send a chat completion request and return the assistant's reply text.
     *
     * @throws TranslationFailedException
     */
    public function chatCompletion(string $systemPrompt, string $userPrompt): string
    {
        $url = rtrim($this->endpoint, '/') .
            "/openai/deployments/{$this->deployment}/chat/completions" .
            "?api-version={$this->apiVersion}";

        $response = Http::withHeaders($this->buildAuthHeaders())
            ->timeout($this->timeout)
            ->post($url, [
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $userPrompt],
                ],
                'temperature' => 0,
            ]);

        if ($response->failed()) {
            throw new TranslationFailedException(
                'Azure OpenAI request failed: ' . $response->status() . ' ' . $response->body()
            );
        }

        $data = $response->json();

        $translatedText = $data['choices'][0]['message']['content'] ?? null;

        if ($translatedText === null) {
            throw new TranslationFailedException('Unexpected response from Azure OpenAI: missing choices[0].message.content');
        }

        return trim($translatedText);
    }

    /**
     * Build auth headers: prefer service principal token, fall back to api-key.
     *
     * @throws TranslationFailedException
     */
    private function buildAuthHeaders(): array
    {
        if ($this->tenantId && $this->applicationId && $this->clientSecret) {
            return [
                'Authorization' => 'Bearer ' . $this->fetchAccessToken(),
                'Content-Type'  => 'application/json',
            ];
        }

        return [
            'api-key'      => $this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Fetch an Azure AD access token using client credentials, cached until expiry.
     *
     * @throws TranslationFailedException
     */
    public function fetchAccessToken(): string
    {
        $cacheKey = 'azure_openai_access_token_' .
            md5($this->institutionId . $this->tenantId . $this->applicationId);

        return Cache::remember($cacheKey, 3300, function () {
            $tokenUrl = "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token";

            $response = Http::asForm()->post($tokenUrl, [
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->applicationId,
                'client_secret' => $this->clientSecret,
                'scope'         => 'https://cognitiveservices.azure.com/.default',
            ]);

            if ($response->failed()) {
                throw new TranslationFailedException(
                    'Failed to obtain Azure AD access token: ' . $response->status() . ' ' . $response->body()
                );
            }

            $token = $response->json('access_token');

            if (empty($token)) {
                throw new TranslationFailedException('Azure AD token response missing access_token field');
            }

            return $token;
        });
    }
}
