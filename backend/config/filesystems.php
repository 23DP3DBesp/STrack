<?php

return [
    'default' => env('FILESYSTEM_DISK', 'private'),

    'disks' => [
        'private' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'throw' => false,
        ],

        'backup' => [
            'driver' => 'local',
            'root' => storage_path('app/backup'),
            'throw' => false,
        ],
    ],

    'links' => [],
];
