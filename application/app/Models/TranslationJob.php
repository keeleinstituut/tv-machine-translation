<?php

namespace App\Models;

use App\Enums\ProviderName;
use App\Enums\TranslationJobStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TranslationJob extends Model
{
    use HasUuids;

    protected $table = 'translation_jobs';

    protected $fillable = [
        'id',
        'provider',
        'type',
        'status',
        'source_language',
        'target_language',
        'options',
        'input_text',
        'output_text',
        'original_filename',
        'external_request_id',
        'translated_storage_path',
        'error_message',
        'institution_user_id',
    ];

    protected $casts = [
        'options'  => 'array',
        'status'   => TranslationJobStatus::class,
        'provider' => ProviderName::class,
    ];

    public function isPending(): bool
    {
        return $this->status === TranslationJobStatus::Pending;
    }

    public function isProcessing(): bool
    {
        return $this->status === TranslationJobStatus::Processing;
    }

    public function isCompleted(): bool
    {
        return $this->status === TranslationJobStatus::Completed;
    }

    public function isFailed(): bool
    {
        return $this->status === TranslationJobStatus::Failed;
    }

    public function isStillInProgress(): bool
    {
        return in_array($this->status, [
            TranslationJobStatus::Pending,
            TranslationJobStatus::Processing,
        ]);
    }

    public function isTextJob(): bool
    {
        return $this->type === 'text';
    }

    public function isFileJob(): bool
    {
        return $this->type === 'file';
    }
}
