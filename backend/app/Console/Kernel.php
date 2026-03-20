<?php

namespace App\Console;

use App\Console\Commands\BackupDatabaseCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        BackupDatabaseCommand::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('docbox:backup')->dailyAt('02:30');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
