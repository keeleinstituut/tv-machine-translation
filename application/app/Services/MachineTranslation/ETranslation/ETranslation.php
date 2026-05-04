<?php

namespace App\Services\MachineTranslation\ETranslation;

use App\Enums\ProviderName;
use App\Enums\TranslationJobStatus;
use App\Jobs\PollETranslationJob;
use App\Models\TranslationJob;
use App\Services\MachineTranslation\Contracts\MachineTranslationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ETranslation implements MachineTranslationService
{
    private ETranslationApiClient $apiClient;

    public function __construct()
    {
        $this->apiClient = new ETranslationApiClient();
    }

    public function getOptions(): array
    {
        $allowedDomains = config('machine-translation.etranslation.domains', []);

        $raw = json_decode(
            file_get_contents(resource_path('etranslation/getDomains.json')),
            true
        );

        $combinations = [];
        foreach ($raw as $entry) {
            $domainCode = $entry['domain'];
            if (!in_array($domainCode, $allowedDomains)) continue;

            foreach ($entry['languagePairs'] as $pair) {
                $parts  = explode('-', $pair);
                $source = strtolower($parts[0]);
                $target = strtolower($parts[1]);
                if ($source === $target) continue;
                $combinations[$source][$target][] = $domainCode;
            }
        }

        foreach ($combinations as $src => &$targets) {
            foreach ($targets as $tgt => &$modes) {
                $modes = array_values(array_unique($modes));
            }
            ksort($targets);
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
        $jobId = Str::uuid()->toString();

        $job = TranslationJob::create([
            'id'                  => $jobId,
            'provider'            => ProviderName::ETranslation->value,
            'type'                => 'text',
            'status'              => TranslationJobStatus::Processing->value,
            'source_language'     => $sourceLanguage,
            'target_language'     => $targetLanguage,
            'options'             => $options,
            'input_text'          => $text,
            'institution_user_id' => $institutionUserId,
        ]);

        // $callbackUrl = rtrim(config('machine-translation.etranslation.callback_url'), '/')
        //     . '/' . $jobId;
        $callbackUrl = $this->constructCallbackUrl($jobId);

        $domain = $options['domain'] ?? 'GEN';

        $externalRequestId = $this->apiClient->translateSnippet(
            $text,
            $sourceLanguage,
            $targetLanguage,
            $domain,
            $callbackUrl
        );

        $job->update(['external_request_id' => $externalRequestId]);

        // Safety-net polling in case the callback never fires
        PollETranslationJob::dispatch($job)->delay(now()->addMinutes(5));

        return $job->fresh();
    }

    public function supportsFileTranslation(): bool
    {
        return true;
    }

    public function isConfiguredForInstitution(): bool
    {
        return true;
    }

    public function isShowUsageConfirmationEnabled(): bool {
        return false;
    }

    public function submitFileTranslation(
        UploadedFile $file,
        string       $sourceLanguage,
        string       $targetLanguage,
        array        $options = [],
        string       $institutionUserId = ''
    ): TranslationJob {
        $jobId = Str::uuid()->toString();

        $job = TranslationJob::create([
            'id'                  => $jobId,
            'provider'            => ProviderName::ETranslation->value,
            'type'                => 'file',
            'status'              => TranslationJobStatus::Processing->value,
            'source_language'     => $sourceLanguage,
            'target_language'     => $targetLanguage,
            'options'             => $options,
            'original_filename'   => $file->getClientOriginalName(),
            'institution_user_id' => $institutionUserId,
        ]);

        // $callbackUrl = rtrim(config('machine-translation.etranslation.callback_url'), '/')
        //     . '/' . $jobId;
        $callbackUrl = $this->constructCallbackUrl($jobId);

        $domain = $options['domain'] ?? 'GEN';

        $externalRequestId = $this->apiClient->submitDocument(
            $file,
            $sourceLanguage,
            $targetLanguage,
            $domain,
            $callbackUrl
        );

        $job->update(['external_request_id' => $externalRequestId]);

        // Safety-net polling in case the callback never fires
        PollETranslationJob::dispatch($job)->delay(now()->addMinutes(5));

        return $job->fresh();
    }

    public function pollTranslationStatus(TranslationJob $job): TranslationJob
    {
        // eTranslation does not expose a polling endpoint — translation delivery
        // is push-only (callback). This method is called by PollETranslationJob
        // as a safety net to check if the result was stored.
        if ($job->isFileJob()) {
            if ($job->translated_storage_path && Storage::exists($job->translated_storage_path)) {
                $job->update(['status' => TranslationJobStatus::Completed->value]);
            }
        } elseif ($job->isTextJob()) {
            if ($job->output_text !== null) {
                $job->update(['status' => TranslationJobStatus::Completed->value]);
            }
        }

        return $job->fresh();
    }

    public function getTranslatedFileContent(TranslationJob $job): array
    {
        if (! $job->isCompleted()) {
            throw new \RuntimeException('File translation job is not completed yet.');
        }

        $content  = Storage::get($job->translated_storage_path);
        $mimeType = Storage::mimeType($job->translated_storage_path) ?: 'application/octet-stream';

        return [
            'content'   => $content,
            'filename'  => 'translated_' . $job->original_filename,
            'mime_type' => $mimeType,
        ];
    }

    private function constructCallbackUrl($jobId): string
    {
        $rawBaseUrl = config('machine-translation.etranslation.callback_url');
        $basicAuthUsername = config('machine-translation.etranslation.callback_basic_auth_username');
        $basicAuthPassword = config('machine-translation.etranslation.callback_basic_auth_password');

        preg_match_all('/(^https?:\/\/)(.+$)/', $rawBaseUrl, $baseUrlParts);
        $protocolPart = $baseUrlParts[1][0];
        $restPart = $baseUrlParts[2][0];

        $basicAuthPart = "";

        if ($basicAuthUsername && $basicAuthPassword) {
            $basicAuthPart = "$basicAuthUsername:$basicAuthPassword";
        }

        $baseUrl = $protocolPart . $basicAuthPart . '@' . $restPart;

        return rtrim($baseUrl, '/') . '/' . $jobId;
    }
}
