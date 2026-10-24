<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => env('AWS_BUCKET') ? [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ] : [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        // Payment proofs, property documents and support attachments live here.
        // Prefer a dedicated non-public bucket: the "public" disk above may be
        // served over a public URL, and these files must never be.
        'private' => env('AWS_PRIVATE_BUCKET', env('AWS_BUCKET')) ? [
            'driver' => 's3',
            'key' => env('AWS_PRIVATE_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID')),
            'secret' => env('AWS_PRIVATE_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY')),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_PRIVATE_BUCKET', env('AWS_BUCKET')),
            'url' => null,
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'visibility' => 'private',
            // Never swallow write failures: a silent `false` from store() would
            // save a payment with no proof attached.
            'throw' => true,
            'report' => true,
        ] : [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => true,
            'report' => true,
        ],

        // Nightly database dumps and copies of every uploaded file (backup:run).
        // Must be a different provider or account from the app's own storage:
        // in Sept 2026 one billing lapse deleted the database and both app
        // buckets together, because they all lived in the same place.
        'backups' => [
            'driver' => 's3',
            'key' => env('BACKUP_ACCESS_KEY_ID'),
            'secret' => env('BACKUP_SECRET_ACCESS_KEY'),
            'region' => env('BACKUP_REGION', 'auto'),
            'bucket' => env('BACKUP_BUCKET'),
            'endpoint' => env('BACKUP_ENDPOINT'),
            'use_path_style_endpoint' => env('BACKUP_USE_PATH_STYLE_ENDPOINT', false),
            'visibility' => 'private',
            'throw' => true,
            'report' => true,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
