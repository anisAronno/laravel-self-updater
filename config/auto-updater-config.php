<?php

/**
 * Laravel Auto Updater configuration file.
 * This file is used to configure the Laravel Auto Updater package.
 */
return [
    'release_url' => env('RELEASE_URL', 'https://github.com/anisAronno/laravel-starter'),
    'purchase_key' => env('PURCHASE_KEY', null),
    'request_timeout' => 120,
    'exclude_items' => [
        '.env',
        '.git',
        'storage',
        'node_modules',
        'vendor',
        '.htaccess',
        'public/.htaccess',
    ],
    'middleware' => ['web'],
];
