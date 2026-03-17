<?php

namespace App\Services\MachineTranslation\Contracts;

use App\Models\TranslationJob;
use Illuminate\Http\UploadedFile;

interface MachineTranslationService
{
    /**
     * Returns provider-specific options to populate the UI form.
     * Shape varies per provider and is returned as-is from the API.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array;

    /**
     * Submit text for translation. Always async — returns a TranslationJob.
     * Azure OpenAI resolves it immediately (status=completed, output_text set).
     * eTranslation returns status=processing and delivers result via callback.
     *
     * @throws \App\Exceptions\TranslationFailedException
     */
    public function submitTextTranslation(
        string $text,
        string $sourceLanguage,
        string $targetLanguage,
        array  $options = [],
        string $institutionUserId = ''
    ): TranslationJob;

    /**
     * Whether this provider supports file translation.
     */
    public function supportsFileTranslation(): bool;

    /**
     * Submit a file for asynchronous translation.
     * Returns a TranslationJob with status 'processing'.
     *
     * @throws \BadMethodCallException            when provider doesn't support file translation
     * @throws \App\Exceptions\TranslationFailedException
     */
    public function submitFileTranslation(
        UploadedFile $file,
        string       $sourceLanguage,
        string       $targetLanguage,
        array        $options = [],
        string       $institutionUserId = ''
    ): TranslationJob;

    /**
     * Poll the external API and update the TranslationJob status.
     *
     * @throws \BadMethodCallException
     */
    public function pollTranslationStatus(TranslationJob $job): TranslationJob;

    /**
     * Retrieve translated file content for a completed job.
     *
     * @return array{content: string, filename: string, mime_type: string}
     * @throws \BadMethodCallException
     * @throws \App\Exceptions\TranslationFailedException
     */
    public function getTranslatedFileContent(TranslationJob $job): array;
}
