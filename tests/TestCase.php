<?php

namespace AnisAronno\LaravelAutoUpdater\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use AnisAronno\LaravelAutoUpdater\LaravelAutoUpdaterServiceProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelAutoUpdaterServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Set up default configuration for testing
        $app['config']->set('auto-updater.release_url', 'https://github.com/user/repo');
        $app['config']->set('auto-updater.purchase_key', 'test-purchase-key');
    }

    public function artisan($command, $parameters = [])
    {
        return parent::artisan($command, $parameters);
    }

    public function be(Authenticatable $user, $driver = null)
    {
        // Implement the method
    }

    public function call($method, $uri, $parameters = [], $files = [], $server = [], $content = null, $changeHistory = true)
    {
        // Implement the method
    }

    public function seed($class = 'DatabaseSeeder')
    {
        // Implement the method
    }
}