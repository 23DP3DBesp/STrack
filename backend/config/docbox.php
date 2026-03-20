<?php

return [
    'backup_disk' => env('BACKUP_DISK', 'backup'),
    'backup_retention_days' => (int) env('BACKUP_RETENTION_DAYS', 14),
    'mysqldump_path' => env('BACKUP_MYSQLDUMP_PATH', 'mysqldump'),
    'upload' => [
        'max_file_kb' => (int) env('DOCBOX_MAX_FILE_KB', 102400),
        'free_plan_quota_mb' => (int) env('DOCBOX_FREE_PLAN_QUOTA_MB', 512),
    ],
];
