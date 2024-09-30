<?php


use AnisAronno\LaravelAutoUpdater\View\Components\AutoUpdater;
use Illuminate\Support\Facades\Route;

// set middleware from config file
$middleware = config('auto-updater.middleware', []);

Route::middleware(array_merge($middleware, ))->prefix('api')->group(function () {
    Route::post('/auto-updater/update', [AutoUpdater::class, 'initiateSystemUpdate'])
    ->name('auto_updater.update');

    Route::get('/auto-updater/check', [AutoUpdater::class, 'checkForSystemUpdates'])
    ->name('auto_updater.check');
});
