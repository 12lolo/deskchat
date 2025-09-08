<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('chat', function (Request $r) {
            $device = $r->header('X-Device-Id') ?: 'no-device';
            // customize JSON on throttle
            $response = function ($seconds) {
                return response()->json([
                    'ok' => false,
                    'error' => 'rate_limited',
                    'retry_after' => $seconds
                ], 429)->header('Retry-After', $seconds);
            };

            return [
                Limit::perMinute(20)->by($r->ip())->response(fn() => $response(60)),
                Limit::perMinute(15)->by($device)->response(fn() => $response(60)),
                Limit::perMinute(300)->by('global')->response(fn() => $response(60)),
            ];
        });
    }
}
