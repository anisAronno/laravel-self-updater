<?php


use AnisAronno\LaravelAutoUpdater\View\Components\AutoUpdater;
use Illuminate\Support\Facades\Route;

// set middleware from config file
$middleware = config('auto-updater.middleware', []);

Route::middleware($middleware)->prefix('api')->group(function () {
    Route::post('/auto-updater/update', [AutoUpdater::class, 'initiateUpdate'])
    ->name('auto_updater.update');

    Route::get('/auto-updater/check', [AutoUpdater::class, 'checkForUpdates'])
    ->name('auto_updater.check');
});
