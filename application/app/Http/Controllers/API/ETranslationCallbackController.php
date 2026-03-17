<?php

namespace App\Http\Controllers\API;

use App\Enums\TranslationJobStatus;
use App\Http\Controllers\Controller;
use App\Models\TranslationJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ETranslationCallbackController extends Controller
{
    /**
     * Handle eTranslation's callback with the translated content.
     *
     * eTranslation calls this endpoint (no auth) with the translated content
     * in the request body or as a multipart attachment.
     * The job ID is passed in the URL path.
     *
     * For text jobs: translated text is stored in output_text.
     * For file jobs: translated file is stored to disk.
     */
    public function __invoke(Request $request, string $jobId): Response
    {
        $job = TranslationJob::find($jobId);

        if (! $job) {
            Log::error('ETranslationCallback: received callback for unknown job', ['job_id' => $jobId]);

            return response('', 204);
        }

        if ($job->isCompleted() || $job->isFailed()) {
            // Already resolved — idempotent response
            return response('', 204);
        }

        $translatedContent = $this->extractTranslatedContent($request);

        if ($translatedContent === null) {
            Log::error('ETranslationCallback: could not extract translated content', [
                'job_id' => $jobId,
                'body'   => $request->getContent(),
            ]);
            $job->update([
                'status'        => TranslationJobStatus::Failed->value,
                'error_message' => 'Callback received but could not extract translated content.',
            ]);

            return response('', 204);
        }

        if ($job->isTextJob()) {
            $job->update([
                'status'      => TranslationJobStatus::Completed->value,
                'output_text' => $translatedContent,
            ]);

            Log::info('ETranslationCallback: text job completed', ['job_id' => $jobId]);
        } else {
            $storagePath = "etranslation-results/{$jobId}/" . $job->original_filename;
            $decodedTranslationContent = base64_decode($translatedContent);
            Storage::put($storagePath, $decodedTranslationContent);

            $job->update([
                'status'                  => TranslationJobStatus::Completed->value,
                'translated_storage_path' => $storagePath,
            ]);

            Log::info('ETranslationCallback: file job completed', ['job_id' => $jobId]);
        }

        return response('', 204);
    }

    private function extractTranslatedContent(Request $request): ?string
    {
        return $request->get('result');

        // // Case 1: eTranslation sends file as multipart
        // if ($request->hasFile('translatedDocument')) {
        //     return $request->file('translatedDocument')->getContent();
        // }

        // // Case 2: Base64-encoded content or translated text in JSON payload
        // $data = $request->json()->all();

        // if (! empty($data['translatedDocument'])) {
        //     $decoded = base64_decode($data['translatedDocument'], strict: true);

        //     return $decoded !== false ? $decoded : null;
        // }

        // // Case 3: Plain translated text in JSON (eTranslation v2 text translation callback)
        // // NOTE: verify the exact field name against the live eTranslation v2 API
        // if (! empty($data['translatedText'])) {
        //     return $data['translatedText'];
        // }

        // if (! empty($data['translation'])) {
        //     return $data['translation'];
        // }

        // // Case 4: Raw body (plain text or file content)
        // $rawBody = $request->getContent();
        // if (! empty($rawBody)) {
        //     return $rawBody;
        // }

        // return null;
    }
}
