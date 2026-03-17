<?php

namespace App\Models;

use App\Enums\FileTranslationJobStatus;
use App\Enums\ProviderName;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FileTranslationJob extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'provider',
        'status',
        'source_language',
        'target_language',
        'options',
        'original_filename',
        'external_request_id',
        'translated_storage_path',
        'error_message',
        'institution_user_id',
    ];

    protected $casts = [
        'options' => 'array',
        'status'  => FileTranslationJobStatus::class,
        'provider' => ProviderName::class,
    ];

    public function isPending(): bool
    {
        return $this->status === FileTranslationJobStatus::Pending;
    }

    public function isProcessing(): bool
    {
        return $this->status === FileTranslationJobStatus::Processing;
    }

    public function isCompleted(): bool
    {
        return $this->status === FileTranslationJobStatus::Completed;
    }

    public function isFailed(): bool
    {
        return $this->status === FileTranslationJobStatus::Failed;
    }

    public function isStillInProgress(): bool
    {
        return in_array($this->status, [
            FileTranslationJobStatus::Pending,
            FileTranslationJobStatus::Processing,
        ]);
    }
}
