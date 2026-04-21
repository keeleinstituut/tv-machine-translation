<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            Route::withoutMiddleware([StartSession::class])
                ->get('/healthz', [\App\Http\Controllers\HealthCheckController::class, 'index'])
                ->name('healthz');

            Route::middleware(['api', 'auth:api'])
                ->prefix('api')
                ->group(base_path('routes/api.php'));
        });
    }
}
