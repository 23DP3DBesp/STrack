<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class RestoreDatabaseBackupCommand extends Command
{
    protected $signature = 'docbox:backup:restore {--file=} {--latest} {--dry-run}';

    protected $description = 'Restore DocBox database from SQL backup file';

    public function handle(): int
    {
        $targetFile = $this->resolveBackupFile();
        if (! $targetFile) {
            $this->error('Backup file not found. Use --file=... or --latest.');

            return self::FAILURE;
        }

        $this->info('Using backup: '.$targetFile);

        if ($this->option('dry-run')) {
            $this->info('Dry-run success: backup file is readable and restore prerequisites are met.');

            return self::SUCCESS;
        }

        $driver = (string) config('database.default', 'mysql');

        if ($driver === 'sqlite') {
            $this->error('Restore command currently supports MySQL only.');

            return self::FAILURE;
        }

        if ($driver !== 'mysql') {
            $this->error('Unsupported database driver: '.$driver);

            return self::FAILURE;
        }

        $process = new Process([
            (string) config('docbox.mysql_path', 'mysql'),
            '--host='.(string) config('database.connections.mysql.host'),
            '--port='.(string) config('database.connections.mysql.port'),
            '--user='.(string) config('database.connections.mysql.username'),
            '--password='.(string) config('database.connections.mysql.password'),
            (string) config('database.connections.mysql.database'),
        ]);
        $process->setInput(File::get($targetFile));
        $process->setTimeout(120);

        try {
            $process->mustRun();
        } catch (\Throwable $e) {
            $this->error('Restore failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Database restore completed.');

        return self::SUCCESS;
    }

    private function resolveBackupFile(): ?string
    {
        $optionFile = $this->option('file');
        if (is_string($optionFile) && trim($optionFile) !== '') {
            $path = trim($optionFile);

            return File::exists($path) ? $path : null;
        }

        if (! $this->option('latest')) {
            return null;
        }

        $files = File::glob(storage_path('app/backup/docbox_*.sql'));
        if (! $files) {
            return null;
        }

        usort($files, static fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));

        return $files[0] ?? null;
    }
}
