<?php

use AnisAronno\LaravelAutoUpdater\Services\ReleaseProviderConfig;
use AnisAronno\LaravelAutoUpdater\Factories\ReleaseProviderFactory;

/**
 * Auto-updater configuration.
 *
 * This file contains settings for the auto-updater, including:
 * - Update source URL
 * - Check frequency
 * - Backup settings
 * - Notification settings
 * - Authentication
 */

/**
 * Auto-updater configuration.
 * 
 * This file contains settings for the auto-updater, including:
 * - Update source URL
 * - Check frequency
 * - Backup settings
 * - Notification settings
 * - Authentication
 */

$release_url = env('RELEASE_URL', 'https://github.com/anisAronno/laravel-starter');
$purchaseKey = env('PURCHASE_KEY', null);

$source = ReleaseProviderFactory::create($release_url, $purchaseKey);
$versionConfig = new ReleaseProviderConfig($source);

return array_merge($versionConfig->getConfig(), [
    "exclude_items" => [
        '.env',
        '.git',
        'storage',
        'node_modules',
        'vendor',
        '.htaccess',
        'public/.htaccess',
    ],
    "middleware" => ['web'],
]);
