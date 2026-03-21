<?php

return [
    'backup_disk' => env('BACKUP_DISK', 'backup'),
    'backup_retention_days' => (int) env('BACKUP_RETENTION_DAYS', 14),
    'mysqldump_path' => env('BACKUP_MYSQLDUMP_PATH', 'mysqldump'),
    'mysql_path' => env('BACKUP_MYSQL_PATH', 'mysql'),
    'security_log_channel' => env('DOCBOX_SECURITY_LOG_CHANNEL', 'security'),
    'cache_ttl_seconds' => (int) env('DOCBOX_CACHE_TTL_SECONDS', 60),
    'upload' => [
        'max_file_kb' => (int) env('DOCBOX_MAX_FILE_KB', 102400),
        'free_plan_quota_mb' => (int) env('DOCBOX_FREE_PLAN_QUOTA_MB', 512),
        'allowed_extensions' => array_values(array_filter(array_map(
            static fn (string $value): string => trim(strtolower($value)),
            explode(',', (string) env('DOCBOX_ALLOWED_EXTENSIONS', 'pdf,doc,docx,xls,xlsx,txt,md,json,csv,xml,jpg,jpeg,png,gif,webp'))
        ))),
        'allowed_mime_types' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('DOCBOX_ALLOWED_MIME_TYPES', 'application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/plain,text/markdown,application/json,text/csv,application/xml,text/xml,image/jpeg,image/png,image/gif,image/webp'))
        ))),
    ],
    'security' => [
        'virus_scan_enabled' => filter_var(env('DOCBOX_VIRUS_SCAN_ENABLED', false), FILTER_VALIDATE_BOOL),
        'virus_scan_binary' => env('DOCBOX_VIRUS_SCAN_BINARY', 'clamscan'),
        'virus_scan_timeout_seconds' => (int) env('DOCBOX_VIRUS_SCAN_TIMEOUT_SECONDS', 20),
        'login_rate_per_minute' => (int) env('DOCBOX_LOGIN_RATE_PER_MINUTE', 10),
        'sensitive_rate_per_minute' => (int) env('DOCBOX_SENSITIVE_RATE_PER_MINUTE', 30),
    ],
];
