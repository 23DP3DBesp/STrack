<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SecurityLogService
{
    public function log(string $event, array $context = []): void
    {
        Log::channel((string) config('docbox.security_log_channel', 'security'))
            ->warning($event, $context);
    }
}
