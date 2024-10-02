<?php

namespace AnisAronno\LaravelAutoUpdater\Tests;

use AnisAronno\LaravelAutoUpdater\LaravelAutoUpdaterServiceProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Orchestra\Testbench\TestCase as Orchestra;
use Mockery;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpConfig();
        Mockery::getConfiguration()->allowMockingNonExistentMethods(true);
    }
    
    protected function getPackageProviders($app)
    {
        return [
            LaravelAutoUpdaterServiceProvider::class,
        ];
    }

    protected function setUpConfig()
    {
        config([
            'auto-updater.release_url' => 'https://github.com/user/repo',
            'auto-updater.purchase_key' => 'test-purchase-key',
            'auto-updater.exclude_items' => ['.env', '.git', 'storage', 'tests'],
            'auto-updater.middleware' => ['web'],
            'auto-updater.require_composer_install' => false,
            'auto-updater.require_composer_update' => false,
        ]);
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
