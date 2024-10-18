<?php

namespace AnisAronno\LaravelSelfUpdater\Tests;

use AnisAronno\LaravelSelfUpdater\LaravelSelfUpdaterServiceProvider;
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
            LaravelSelfUpdaterServiceProvider::class,
        ];
    }

    protected function setUpConfig()
    {
        config([
            'self-updater.release_url' => 'https://github.com/anisAronno/laravel-starter',
            'self-updater.license_key' => 'test-purchase-key',
            'self-updater.exclude_items' => ['.env', '.git', 'storage', 'tests'],
            'self-updater.middleware' => ['web'],
            'self-updater.require_composer_install' => false,
            'self-updater.require_composer_update' => false,
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
