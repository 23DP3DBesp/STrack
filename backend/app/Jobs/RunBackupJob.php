<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class RunBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $timestamp = Carbon::now()->format('Ymd_His');
        $disk = Storage::disk((string) config('docbox.backup_disk'));

        $dbDump = storage_path("app/backup/docbox_{$timestamp}.sql");
        $this->dumpDatabase($dbDump);

        $metadata = [
            'timestamp' => $timestamp,
            'app_env' => config('app.env'),
            'db_database' => config('database.connections.mysql.database'),
        ];

        $disk->put("metadata_{$timestamp}.json", json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->cleanupOldBackups();
    }

    private function dumpDatabase(string $targetPath): void
    {
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $binary = config('docbox.mysqldump_path');

        $command = [
            $binary,
            '--host='.$host,
            '--port='.$port,
            '--user='.$username,
            '--password='.$password,
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            $database,
        ];

        $process = new Process($command);
        $process->mustRun();

        File::put($targetPath, $process->getOutput());
    }

    private function cleanupOldBackups(): void
    {
        $retentionDays = (int) config('docbox.backup_retention_days');
        $threshold = Carbon::now()->subDays($retentionDays)->timestamp;
        $backupPath = storage_path('app/backup');

        if (!File::isDirectory($backupPath)) {
            return;
        }

        foreach (File::files($backupPath) as $file) {
            if ($file->getMTime() < $threshold) {
                File::delete($file->getRealPath());
            }
        }
    }
}
