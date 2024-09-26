<?php

namespace AnisAronno\LaravelAutoUpdater;

use AnisAronno\LaravelAutoUpdater\Contracts\UpdateFetcherInterface;
use AnisAronno\LaravelAutoUpdater\Services\VersionSourceFactory;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use AnisAronno\LaravelAutoUpdater\View\Components\AutoUpdater;
use AnisAronno\LaravelAutoUpdater\Console\Commands\CheckUpdateCommand;
use AnisAronno\LaravelAutoUpdater\Console\Commands\UpdateInitiateCommand;

class LaravelAutoUpdaterServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerCommands();
        $this->mergeConfigFrom(__DIR__.'/../config/auto-updater-config.php', 'auto-updater');

        $this->app->singleton(UpdateFetcherInterface::class, function ($app) {
            $source = config('auto-updater.source', 'github');
            return VersionSourceFactory::createFetcher($source);
        });
    }

    /**
     * Bootstrap the service provider.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerRoutes();
        $this->registerResources();
        $this->registerBladeComponents();

        if ($this->app->runningInConsole()) {
            $this->registerCommands();
        }
    }

    /**
     * Register the package's console commands.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        $this->commands([
            CheckUpdateCommand::class,
            UpdateInitiateCommand::class,
        ]);
    }

    /**
     * Register the package's routes.
     *
     * @return void
     */
    protected function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }

    /**
     * Register the package's resources.
     *
     * @return void
     */
    protected function registerResources(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'auto-updater');
    }

    /**
     * Register the package's Blade components.
     *
     * @return void
     */
    protected function registerBladeComponents(): void
    {
        Blade::component('auto-updater', AutoUpdater::class);
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__.'/../config/auto-updater-config.php' => config_path('auto-updater.php'),
        ], 'auto-updater-config');

        $this->publishes([
            __DIR__.'/../resources/css' => public_path('vendor/auto-updater/css'),
            __DIR__.'/../resources/js' => public_path('vendor/auto-updater/js'),
        ], 'auto-updater-assets');

        $this->publishes([
            __DIR__.'/../resources/views/components' => resource_path('views/vendor/auto-updater'),
        ], 'auto-updater-views');
    }
}
