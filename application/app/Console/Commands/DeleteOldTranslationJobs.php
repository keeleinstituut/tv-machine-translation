<?php

namespace App\Console\Commands;

use App\Models\TranslationJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DeleteOldTranslationJobs extends Command
{
    protected $signature = 'app:delete-old-translation-jobs';
    protected $description = 'Delete translation jobs older than 1 day';

    public function handle(): void
    {
        $cutoff = now()->subDay();

        $jobs = TranslationJob::where('created_at', '<', $cutoff)->get();

        foreach ($jobs as $job) {
            if ($job->translated_storage_path) {
                Storage::delete($job->translated_storage_path);
            }
        }

        $count = TranslationJob::where('created_at', '<', $cutoff)->delete();

        $this->info("Deleted {$count} translation job(s) older than 1 day.");
    }
}
