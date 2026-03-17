<?php

namespace App\Providers;

use App\Services\MachineTranslation\MachineTranslationPickerService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MachineTranslationPickerService::class);
    }

    public function boot(): void
    {
        //
    }
}
