<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Symfony\Component\Process\Process;

class FileSecurityService
{
    public function scanUploadedFile(UploadedFile $file): array
    {
        if (! (bool) config('docbox.security.virus_scan_enabled', false)) {
            return [
                'status' => 'skipped',
                'reason' => 'disabled',
            ];
        }

        $binary = (string) config('docbox.security.virus_scan_binary', 'clamscan');
        $timeout = max(1, (int) config('docbox.security.virus_scan_timeout_seconds', 20));

        $path = $file->getRealPath();
        if (! $path) {
            return [
                'status' => 'error',
                'reason' => 'tmp_path_missing',
            ];
        }

        $process = new Process([$binary, '--no-summary', '--infected', $path]);
        $process->setTimeout($timeout);

        try {
            $process->run();
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'reason' => 'scanner_failed',
                'message' => $e->getMessage(),
            ];
        }

        if ($process->getExitCode() === 0) {
            return [
                'status' => 'clean',
            ];
        }

        if ($process->getExitCode() === 1) {
            return [
                'status' => 'infected',
                'output' => trim($process->getOutput() ?: $process->getErrorOutput()),
            ];
        }

        return [
            'status' => 'error',
            'reason' => 'scanner_exit_non_zero',
            'exit_code' => $process->getExitCode(),
            'output' => trim($process->getOutput() ?: $process->getErrorOutput()),
        ];
    }
}
