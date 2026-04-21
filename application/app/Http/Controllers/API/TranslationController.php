<?php

namespace App\Http\Controllers\API;

use App\Exceptions\TranslationFailedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\ListTranslationJobsRequest;
use App\Http\Requests\API\TranslateFileRequest;
use App\Http\Requests\API\TranslateTextRequest;
use App\Http\Resources\API\TranslationJobResource;
use App\Models\TranslationJob;
use App\Services\MachineTranslation\MachineTranslationPickerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TranslationController extends Controller
{
    public function __construct(
        private readonly MachineTranslationPickerService $picker
    ) {
    }

    #[OA\Get(
        path: '/translate/jobs',
        summary: 'List all translation jobs for the authenticated user',
        tags: ['Translation'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'processing', 'completed', 'failed'])),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['text', 'file'])),
            new OA\Parameter(name: 'provider', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['etranslation', 'azure_openai'])),
            new OA\Parameter(name: 'source_language', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'en')),
            new OA\Parameter(name: 'target_language', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'et')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of translation jobs'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function listJobs(ListTranslationJobsRequest $request): AnonymousResourceCollection
    {
        $filters           = $request->validated();
        $institutionUserId = (string) (Auth::user()?->institutionUserId ?? '');

        $jobs = TranslationJob::query()
            ->where('institution_user_id', $institutionUserId)
            ->when(isset($filters['id']), fn ($q) => $q->whereIn('id', $filters['id']))
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['type']), fn ($q) => $q->where('type', $filters['type']))
            ->when(isset($filters['provider']), fn ($q) => $q->where('provider', $filters['provider']))
            ->when(isset($filters['source_language']), fn ($q) => $q->where('source_language', $filters['source_language']))
            ->when(isset($filters['target_language']), fn ($q) => $q->where('target_language', $filters['target_language']))
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 15);

        return TranslationJobResource::collection($jobs);
    }

    #[OA\Post(
        path: '/translate/text',
        summary: 'Submit text for translation (always async — returns a job)',
        tags: ['Translation'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['provider', 'text', 'source_language', 'target_language'],
            properties: [
                new OA\Property(property: 'provider', type: 'string', example: 'azure_openai'),
                new OA\Property(property: 'text', type: 'string', example: 'Hello, world!'),
                new OA\Property(property: 'source_language', type: 'string', example: 'en'),
                new OA\Property(property: 'target_language', type: 'string', example: 'et'),
                new OA\Property(property: 'options', type: 'object', nullable: true),
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: 'Translation job created (Azure OpenAI resolves immediately with status=completed)'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function translateText(TranslateTextRequest $request): JsonResponse
    {
        $data    = $request->validated();
        $service = $this->picker->pick($data['provider'], (string) (Auth::user()?->institutionId ?? ''));

        try {
            $job = $service->submitTextTranslation(
                text:              $data['text'],
                sourceLanguage:    $data['source_language'],
                targetLanguage:    $data['target_language'],
                options:           $data['options'] ?? [],
                institutionUserId: (string) (Auth::user()?->institutionUserId ?? ''),
            );
        } catch (TranslationFailedException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return (new TranslationJobResource($job))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/translate/text/{id}/status',
        summary: 'Get the status of an async text translation job',
        tags: ['Translation'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Job status'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function textTranslationStatus(string $id): JsonResponse
    {
        $job = TranslationJob::findOrFail($id);

        $this->authorizeJobAccess($job);

        return (new TranslationJobResource($job))->response();
    }

    #[OA\Post(
        path: '/translate/file',
        summary: 'Submit a file for asynchronous translation (eTranslation only)',
        tags: ['Translation'],
        responses: [
            new OA\Response(response: 201, description: 'File translation job created'),
            new OA\Response(response: 422, description: 'Validation error or provider does not support file translation'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function submitFileTranslation(TranslateFileRequest $request): JsonResponse
    {
        $data    = $request->validated();
        $service = $this->picker->pick($data['provider'], (string) (Auth::user()?->institutionId ?? ''));

        if (! $service->supportsFileTranslation()) {
            return response()->json(['message' => 'This provider does not support file translation.'], 422);
        }

        try {
            $job = $service->submitFileTranslation(
                file:              $request->file('file'),
                sourceLanguage:    $data['source_language'],
                targetLanguage:    $data['target_language'],
                options:           $data['options'] ?? [],
                institutionUserId: (string) (Auth::user()?->institutionUserId ?? ''),
            );
        } catch (TranslationFailedException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return (new TranslationJobResource($job))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/translate/file/{id}/status',
        summary: 'Get the status of an async file translation job',
        tags: ['Translation'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Job status'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function fileTranslationStatus(string $id): JsonResponse
    {
        $job = TranslationJob::findOrFail($id);

        $this->authorizeJobAccess($job);

        return (new TranslationJobResource($job))->response();
    }

    #[OA\Get(
        path: '/translate/file/{id}/download',
        summary: 'Download the translated file for a completed job',
        tags: ['Translation'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'File download'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Job not completed'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function downloadTranslatedFile(string $id): StreamedResponse|JsonResponse
    {
        $job = TranslationJob::findOrFail($id);

        $this->authorizeJobAccess($job);

        if (! $job->isCompleted()) {
            return response()->json(['message' => 'File translation is not completed yet.'], 422);
        }

        $service = $this->picker->pick($job->provider->value);

        try {
            $fileData = $service->getTranslatedFileContent($job);
        } catch (TranslationFailedException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->streamDownload(
            callback: function () use ($fileData) {
                echo $fileData['content'];
            },
            name: $fileData['filename'],
            headers: [
                'Content-Type' => $fileData['mime_type'],
            ]
        );
    }

    private function authorizeJobAccess(TranslationJob $job): void
    {
        $institutionUserId = (string) (Auth::user()?->institutionUserId ?? '');

        if ($institutionUserId !== '' && $job->institution_user_id !== $institutionUserId) {
            abort(403, 'You are not authorized to access this translation job.');
        }
    }
}
