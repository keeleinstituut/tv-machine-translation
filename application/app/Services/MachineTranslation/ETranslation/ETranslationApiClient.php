<?php

namespace App\Services\MachineTranslation\ETranslation;

use App\Exceptions\TranslationFailedException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ETranslationApiClient
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private string $applicationName;
    private int    $timeout;

    public function __construct()
    {
        $this->baseUrl         = config('machine-translation.etranslation.base_url');
        $this->username        = config('machine-translation.etranslation.username');
        $this->password        = config('machine-translation.etranslation.password');
        $this->applicationName = config('machine-translation.etranslation.application_name');
        $this->timeout         = config('machine-translation.etranslation.timeout');
    }

    /**
     * Submit a text snippet for asynchronous translation via eTranslation REST v2.
     * Returns the eTranslation request ID. Result is delivered via the callback URL.
     *
     * @throws TranslationFailedException
     */
    public function translateSnippet(
        string $text,
        string $sourceLanguage,
        string $targetLanguage,
        string $domain,
        string $callbackUrl
    ): string {
        $response = Http::withBasicAuth($this->applicationName, $this->password)
            ->timeout($this->timeout)
            ->post($this->baseUrl, [
                'callerInformation' => [
                    'username'    => $this->username,
                ],
                'textToTranslate'   => $text,
                'sourceLanguage'    => strtoupper($sourceLanguage),
                'targetLanguages'   => [strtoupper($targetLanguage)],
                'domain'            => $domain,
                'deliveries' => [
                    'http' => $callbackUrl,
                ],
                // 'requesterCallback' => $callbackUrl,
            ]);

        if ($response->failed()) {
            throw new TranslationFailedException(
                'eTranslation request failed: ' . $response->status() . ' ' . $response->body()
            );
        }

        $data = $response->json();

        $requestId = $data['requestId'] ?? $data['request-id'] ?? null;

        if ($requestId === null) {
            throw new TranslationFailedException(
                'eTranslation did not return a request ID: ' . $response->body()
            );
        }

        return (string) $requestId;
    }

    /**
     * Submit a document for asynchronous translation.
     * Returns the eTranslation request ID.
     *
     * @throws TranslationFailedException
     */
    public function submitDocument(
        UploadedFile $file,
        string       $sourceLanguage,
        string       $targetLanguage,
        string       $domain,
        string       $callbackUrl
    ): string {
        $fileContent = base64_encode($file->getContent());
        $fileNameSplit = Str::of($file->getClientOriginalName())->explode('.');
        $fileFilename = $fileNameSplit[0];
        $fileFormat = $fileNameSplit[1];

        $response = Http::withBasicAuth($this->applicationName, $this->password)
            ->timeout($this->timeout)
            // ->attach('documentToTranslate', $file->getContent(), $file->getClientOriginalName())
            ->post($this->baseUrl, [
                'callerInformation' => [
                    'username'    => $this->username,
                ],
                'documentToTranslate' => [
                    'document' => [
                        'content' => $fileContent,
                        'format' => $fileFormat,
                        'filename' => $fileFilename,
                    ]
                ],
                'sourceLanguage'  => strtoupper($sourceLanguage),
                'targetLanguages' => [strtoupper($targetLanguage)],
                'domain'          => $domain,
                'deliveries' => [
                    'http' => $callbackUrl,
                ],
            ]);

        if ($response->failed()) {
            throw new TranslationFailedException(
                'eTranslation document submission failed: ' . $response->status() . ' ' . $response->body()
            );
        }

        $data = $response->json();

        // eTranslation returns a numeric request ID
        $requestId = $data['requestId'] ?? $data['request-id'] ?? null;

        if ($requestId === null) {
            throw new TranslationFailedException(
                'eTranslation did not return a request ID: ' . $response->body()
            );
        }

        return (string) $requestId;
    }
}
