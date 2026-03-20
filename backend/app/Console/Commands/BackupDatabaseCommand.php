<?php

namespace App\Console\Commands;

use App\Jobs\RunBackupJob;
use Illuminate\Console\Command;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'docbox:backup';

    protected $description = 'Run DocBox database backup';

    public function handle(): int
    {
        RunBackupJob::dispatchSync();

        $this->info('Backup completed successfully');

        return self::SUCCESS;
    }
}
