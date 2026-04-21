<?php

use App\Http\Controllers\API;
use App\Http\Controllers\HealthCheckController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Machine Translation API Routes
|--------------------------------------------------------------------------
*/

// Providers
Route::prefix('/providers')
    ->controller(API\ProviderController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('machine-translation.providers.index');
        Route::get('/{provider}/options', 'options')->name('machine-translation.providers.options');
    });

// Translation
Route::prefix('/translate')
    ->controller(API\TranslationController::class)
    ->group(function (): void {
        Route::get('/jobs', 'listJobs')->name('machine-translation.translate.jobs.index');
        Route::post('/text', 'translateText')->name('machine-translation.translate.text');
        Route::get('/text/{id}/status', 'textTranslationStatus')
            ->whereUuid('id')
            ->name('machine-translation.translate.text.status');
        Route::post('/file', 'submitFileTranslation')->name('machine-translation.translate.file');
        Route::get('/file/{id}/status', 'fileTranslationStatus')
            ->whereUuid('id')
            ->name('machine-translation.translate.file.status');
        Route::get('/file/{id}/download', 'downloadTranslatedFile')
            ->whereUuid('id')
            ->name('machine-translation.translate.file.download');
    });

// Institution-scoped settings
Route::prefix('/settings')
    ->controller(API\InstitutionSettingsController::class)
    ->group(function (): void {
        Route::get('/', 'show')
            ->name('machine-translation.settings.show');
        Route::put('/', 'update')
            ->name('machine-translation.settings.update');
    });

// eTranslation callback — no auth, called by EU Commission server
Route::post(
    '/callback/etranslation/{jobId}',
    API\ETranslationCallbackController::class
)
    ->whereUuid('jobId')
    ->withoutMiddleware(['auth:api'])
    ->name('machine-translation.callback.etranslation');
