<?php

/**
 * Laravel Self Updater configuration file.
 * This file is used to configure the Laravel Self Updater package.
 */
return [
    'release_url' => env('RELEASE_URL', 'https://github.com/anisAronno/laravel-starter'),
    'license_key' => env('LICENSE_KEY') ?? env('PURCHASE_KEY', 'your-license-key'),
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
    'require_composer_install' => false,
    'require_composer_update' => false,
];
