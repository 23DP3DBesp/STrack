<?php

namespace App\Console;

use App\Console\Commands\BackupDatabaseCommand;
use App\Console\Commands\RestoreDatabaseBackupCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        BackupDatabaseCommand::class,
        RestoreDatabaseBackupCommand::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('docbox:backup')->dailyAt('02:30');
        $schedule->command('docbox:backup:restore --latest --dry-run')->weeklyOn(1, '03:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
