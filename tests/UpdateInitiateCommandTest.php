<?php

namespace AnisAronno\LaravelAutoUpdater\Tests;

use AnisAronno\LaravelAutoUpdater\Console\Commands\UpdateInitiateCommand;
use AnisAronno\LaravelAutoUpdater\Services\ReleaseService;
use AnisAronno\LaravelAutoUpdater\Services\UpdateOrchestrator;
use Mockery;
use Orchestra\Testbench\TestCase;

class UpdateInitiateCommandTest extends TestCase
{
    protected $releaseService;
    protected $updateOrchestrator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->releaseService = Mockery::mock(ReleaseService::class);
        $this->updateOrchestrator = Mockery::mock(UpdateOrchestrator::class);
        $this->app->instance(ReleaseService::class, $this->releaseService);
        $this->app->instance(UpdateOrchestrator::class, $this->updateOrchestrator);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [\AnisAronno\LaravelAutoUpdater\LaravelAutoUpdaterServiceProvider::class];
    }

    public function testInitiateUpdateForSpecificVersion()
    {
        $this->releaseService
            ->shouldReceive('collectReleaseData')
            ->with('1.2.0')
            ->once()
            ->andReturn([
                'version' => '1.2.0',
                'download_url' => 'https://example.com/download/1.2.0',
            ]);

        $this->updateOrchestrator
            ->shouldReceive('processUpdate')
            ->with([
                'version' => '1.2.0',
                'download_url' => 'https://example.com/download/1.2.0',
            ], Mockery::type(UpdateInitiateCommand::class))
            ->once();

        $this->artisan('update:initiate 1.2.0')
            ->expectsOutput('Initiating update for version: 1.2.0')
            ->expectsOutput('Update process has been started.')
            ->expectsOutput('Update process has been completed successfully.')
            ->assertExitCode(0);
    }

    public function testInitiateUpdateForLatestVersion()
    {
        $this->releaseService
            ->shouldReceive('collectReleaseData')
            ->with(null) // No specific version passed, so it should fetch the latest version
            ->once()
            ->andReturn([
                'version' => '1.3.0',
                'download_url' => 'https://example.com/download/1.3.0',
            ]);

        $this->updateOrchestrator
            ->shouldReceive('processUpdate')
            ->with([
                'version' => '1.3.0',
                'download_url' => 'https://example.com/download/1.3.0',
            ], Mockery::type(UpdateInitiateCommand::class))
            ->once();

        $this->artisan('update:initiate')
            ->expectsOutput('Initiating update for the latest version.')
            ->expectsOutput('Update process has been started.')
            ->expectsOutput('Update process has been completed successfully.')
            ->assertExitCode(0);
    }

    public function testNoUpdateAvailable()
    {
        $this->releaseService
            ->shouldReceive('collectReleaseData')
            ->with(null)
            ->once()
            ->andReturn([]);

        $this->artisan('update:initiate')
            ->expectsOutput('Initiating update for the latest version.')
            ->expectsOutput('No update available.')
            ->assertExitCode(0);
    }

    public function testUpdateFailsDueToException()
    {
        $this->releaseService
            ->shouldReceive('collectReleaseData')
            ->with(null)
            ->once()
            ->andThrow(new \Exception('Test exception'));

        $this->artisan('update:initiate')
            ->expectsOutput('Initiating update for the latest version.')
            ->expectsOutput('Update failed: Test exception')
            ->assertExitCode(1);
    }

    public function testUpdateFailsWhenMissingReleaseData()
    {
        $this->releaseService
            ->shouldReceive('collectReleaseData')
            ->with('1.2.0')
            ->once()
            ->andReturn([
                'version' => null, // Missing data
                'download_url' => null,
            ]);

        $this->artisan('update:initiate 1.2.0')
            ->expectsOutput('Initiating update for version: 1.2.0')
            ->expectsOutput('No update available.')
            ->assertExitCode(0);
    }
}