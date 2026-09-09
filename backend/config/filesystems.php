<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => false,
            'throw' => false,
        ],
        'documents' => [
            'driver' => 'local',
            'root' => storage_path('app/private/documents'),
            'serve' => false,
            'throw' => true,
        ],
    ],
];
