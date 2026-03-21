<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupRestoreCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_restore_command_dry_run_with_latest_backup(): void
    {
        $backupPath = storage_path('app/backup');
        File::ensureDirectoryExists($backupPath);
        File::put($backupPath.'/docbox_20260321_010203.sql', '-- backup --');

        $this->artisan('docbox:backup:restore --latest --dry-run')
            ->assertExitCode(0);
    }
}
