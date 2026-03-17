<?php

namespace App\Jobs;

use App\Enums\TranslationJobStatus;
use App\Models\TranslationJob;
use App\Services\MachineTranslation\ETranslation\ETranslation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PollETranslationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum age in hours before giving up on a job.
     */
    private const MAX_AGE_HOURS = 24;

    /**
     * Maximum delay between retries in minutes.
     */
    private const MAX_DELAY_MINUTES = 30;

    public int $tries = 0; // Unlimited retries — we self-manage via re-dispatch
    public int $maxExceptions = 3;

    public function __construct(private readonly TranslationJob $jobModel)
    {
    }

    public function handle(): void
    {
        $this->jobModel->refresh();

        // If callback already resolved the job, nothing to do
        if (! $this->jobModel->isStillInProgress()) {
            return;
        }

        // Give up after MAX_AGE_HOURS
        if ($this->jobModel->created_at->diffInHours(now()) >= self::MAX_AGE_HOURS) {
            Log::warning('PollETranslationJob: giving up on job after ' . self::MAX_AGE_HOURS . ' hours', [
                'job_id' => $this->jobModel->id,
            ]);
            $this->jobModel->update([
                'status'        => TranslationJobStatus::Failed->value,
                'error_message' => 'Translation timed out after ' . self::MAX_AGE_HOURS . ' hours.',
            ]);

            return;
        }

        $service = new ETranslation();
        $service->pollTranslationStatus($this->jobModel);

        $this->jobModel->refresh();

        if ($this->jobModel->isStillInProgress()) {
            // Re-dispatch with exponential backoff
            $ageMinutes   = $this->jobModel->created_at->diffInMinutes(now());
            $delayMinutes = min(self::MAX_DELAY_MINUTES, max(5, (int) ($ageMinutes * 0.2)));

            static::dispatch($this->jobModel)->delay(now()->addMinutes($delayMinutes));
        }
    }
}
