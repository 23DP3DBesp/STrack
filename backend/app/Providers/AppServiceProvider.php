<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(10)->by((string) $request->ip());
        });

        RateLimiter::for('verification-email', function (Request $request): Limit {
            $key = Str::lower((string) ($request->input('email') ?: $request->input('login') ?: $request->ip()));

            return Limit::perMinute(3)->by($key.'|'.$request->ip());
        });
    }
}
