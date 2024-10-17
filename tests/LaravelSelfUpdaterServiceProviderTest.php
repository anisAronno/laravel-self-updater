<?php

namespace AnisAronno\LaravelSelfUpdater\Tests;

use AnisAronno\LaravelSelfUpdater\Console\Commands\CheckUpdateCommand;
use AnisAronno\LaravelSelfUpdater\Console\Commands\UpdateInitiateCommand;
use AnisAronno\LaravelSelfUpdater\Contracts\VCSProviderInterface;
use AnisAronno\LaravelSelfUpdater\LaravelSelfUpdaterServiceProvider;

class LaravelSelfUpdaterServiceProviderTest extends TestCase
{
    public function testServiceProviderIsLoaded()
    {
        $this->assertTrue(
            $this->app->providerIsLoaded(LaravelSelfUpdaterServiceProvider::class),
            'The LaravelSelfUpdaterServiceProvider is not loaded'
        );
    }

    public function testServiceProviderRegistersCommands()
    {
        $this->assertTrue($this->app->make(CheckUpdateCommand::class) !== null);
        $this->assertTrue($this->app->make(UpdateInitiateCommand::class) !== null);
    }

    public function testServiceProviderRegistersConfig()
    {
        $this->assertTrue($this->app->make('config')->has('self-updater'));
    }

    public function testConfigurationIsPublished()
    {
        $this->artisan('vendor:publish', ['--provider' => LaravelSelfUpdaterServiceProvider::class])
            ->assertExitCode(0);

        $this->assertFileExists(config_path('self-updater.php'));
    }

    public function testServiceProviderBindsVCSProviderInterface()
    {
        $this->app['config']->set('self-updater.release_url', 'https://github.com/anisAronno/laravel-starter');

        $instance = $this->app->make(VCSProviderInterface::class);
        $this->assertInstanceOf(VCSProviderInterface::class, $instance);
    }

    public function testServiceProviderRegistersViews()
    {
        $this->assertArrayHasKey('self-updater', $this->app['view']->getFinder()->getHints());
    }
}
