<?php

namespace AnisAronno\LaravelAutoUpdater\Tests;

use AnisAronno\LaravelAutoUpdater\LaravelAutoUpdaterServiceProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Mockery;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
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
            'auto-updater.release_url' => 'https://github.com/anisAronno/laravel-starter',
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
        $this->app['auth']->guard($driver)->setUser($user);

        $this->app->instance('user', $user);

        return $this;
    }

    public function call($method, $uri, $parameters = [], $files = [], $server = [], $content = null, $changeHistory = true)
    {
        return parent::call($method, $uri, $parameters, $files, $server, $content, $changeHistory);
    }

    public function seed($class = 'DatabaseSeeder')
    {
        $this->artisan('db:seed', ['--class' => $class]);
    }
}
