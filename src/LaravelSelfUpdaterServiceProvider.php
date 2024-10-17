<?php

namespace AnisAronno\LaravelSelfUpdater;

use AnisAronno\LaravelSelfUpdater\Console\Commands\CheckUpdateCommand;
use AnisAronno\LaravelSelfUpdater\Console\Commands\UpdateInitiateCommand;
use AnisAronno\LaravelSelfUpdater\Contracts\VCSProviderInterface;
use AnisAronno\LaravelSelfUpdater\Services\VCSProvider\VCSProviderFactory;
use AnisAronno\LaravelSelfUpdater\View\Components\SelfUpdater;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Class LaravelSelfUpdaterServiceProvider
 *
 * Service provider for the Laravel Self Updater package.
 */
class LaravelSelfUpdaterServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->registerCommands();
        $this->mergeConfigFrom(__DIR__.'/../config/self-updater-config.php', 'self-updater');

        $this->app->singleton(VCSProviderInterface::class, function () {
            $releaseUrl = config('self-updater.release_url');

            return VCSProviderFactory::create($releaseUrl);
        });
    }

    /**
     * Bootstrap the service provider.
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
     */
    protected function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }

    /**
     * Register the package's resources.
     */
    protected function registerResources(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'self-updater');
    }

    /**
     * Register the package's Blade components.
     */
    protected function registerBladeComponents(): void
    {
        Blade::component('self-updater', SelfUpdater::class);
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__.'/../config/self-updater-config.php' => config_path('self-updater.php'),
        ], 'self-updater-config');

        $this->publishes([
            __DIR__.'/../resources/css' => public_path('vendor/self-updater/css'),
            __DIR__.'/../resources/js' => public_path('vendor/self-updater/js'),
        ], 'self-updater-assets');

        $this->publishes([
            __DIR__.'/../resources/views/components' => resource_path('views/vendor/self-updater'),
        ], 'self-updater-views');
    }
}
