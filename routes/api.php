<?php

use AnisAronno\LaravelSelfUpdater\View\Components\SelfUpdater;
use Illuminate\Support\Facades\Route;

// set middleware from config file
$middleware = config('self-updater.middleware', []);

Route::middleware($middleware)->prefix('api')->group(function () {
    Route::post('/self-updater/update', [SelfUpdater::class, 'initiateSystemUpdate'])
        ->name('self_updater.update');

    Route::get('/self-updater/check', [SelfUpdater::class, 'checkForSystemUpdates'])
        ->name('self_updater.check');
});
