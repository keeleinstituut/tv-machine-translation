<?php

namespace App\Http\Resources\API;

use App\Models\FileTranslationJob;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FileTranslationJob */
class FileTranslationJobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'provider'          => $this->provider->value,
            'status'            => $this->status->value,
            'source_language'   => $this->source_language,
            'target_language'   => $this->target_language,
            'original_filename' => $this->original_filename,
            'created_at'        => $this->created_at?->toIso8601String(),
        ];
    }
}
