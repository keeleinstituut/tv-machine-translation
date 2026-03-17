<?php

namespace App\Http\Resources\API;

use App\Models\TranslationJob;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TranslationJob */
class TranslationJobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'provider'          => $this->provider->value,
            'type'              => $this->type,
            'status'            => $this->status->value,
            'source_language'   => $this->source_language,
            'target_language'   => $this->target_language,
            'output_text'       => $this->output_text,
            'original_filename' => $this->original_filename,
            'created_at'        => $this->created_at?->toISOString(),
        ];
    }
}
