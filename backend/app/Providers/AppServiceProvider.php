<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            $perMinute = max(1, (int) config('docbox.security.login_rate_per_minute', 10));

            return Limit::perMinute($perMinute)->by((string) $request->ip());
        });

        RateLimiter::for('sensitive', function (Request $request): Limit {
            $perMinute = max(1, (int) config('docbox.security.sensitive_rate_per_minute', 30));
            $identity = (string) ($request->user()?->id ?? $request->ip());

            return Limit::perMinute($perMinute)->by($identity);
        });
    }
}
