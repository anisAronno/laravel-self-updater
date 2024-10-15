<?php

namespace Tests\Unit\Services;

use AnisAronno\LaravelAutoUpdater\Contracts\VCSProviderInterface;
use AnisAronno\LaravelAutoUpdater\Services\ReleaseService;
use AnisAronno\LaravelAutoUpdater\Tests\TestCase;
use Illuminate\Support\Facades\File;
use Mockery;

class ReleaseServiceTest extends TestCase
{
    /** @test */
    public function testReturnsTheCurrentVersionWhenComposerFileExistsAndContainsVersion()
    {
        // Mocking the composer.json content
        File::shouldReceive('exists')
            ->once()
            ->with(base_path('composer.json'))
            ->andReturn(true);

        File::shouldReceive('get')
            ->once()
            ->with(base_path('composer.json'))
            ->andReturn(json_encode(['version' => '1.2.3']));

        $fetcher = Mockery::mock(VCSProviderInterface::class);

        /** @var \Mockery\MockInterface|VCSProviderInterface $fetcher */
        $service = new ReleaseService($fetcher);

        // Assert that the version is as expected
        $this->assertEquals('1.2.3', $service->getCurrentVersion());
    }

    /** @test */
    public function testReturnsDefaultVersionWhenComposerFileExistsButDoesNotContainVersion()
    {
        // Mocking the composer.json without version field
        File::shouldReceive('exists')
            ->once()
            ->with(base_path('composer.json'))
            ->andReturn(true);

        File::shouldReceive('get')
            ->once()
            ->with(base_path('composer.json'))
            ->andReturn(json_encode([]));

        /** @var \Mockery\MockInterface|VCSProviderInterface $fetcher */
        $fetcher = Mockery::mock(VCSProviderInterface::class);
        $service = new ReleaseService($fetcher);

        // Assert that the default version '0.0.0' is returned
        $this->assertEquals('0.0.0', $service->getCurrentVersion());
    }

    /** @test */
    public function testReturnsDefaultVersionWhenComposerFileDoesNotExist()
    {
        // Simulate that the composer.json file does not exist
        File::shouldReceive('exists')
            ->once()
            ->with(base_path('composer.json'))
            ->andReturn(false);

        /** @var \Mockery\MockInterface|VCSProviderInterface $fetcher */
        $fetcher = Mockery::mock(VCSProviderInterface::class);
        $service = new ReleaseService($fetcher);

        // Assert that the default version '0.0.0' is returned
        $this->assertEquals('0.0.0', $service->getCurrentVersion());
    }

    /** @test */
    public function testFetchesReleaseDataForSpecificVersion()
    {
        $fetcher = Mockery::mock(VCSProviderInterface::class);

        // Mocking the fetcher to expect a call to getReleaseByVersion with '1.2.3'
        $fetcher->shouldReceive('getReleaseByVersion')
            ->once()
            ->with('1.2.3')
            ->andReturn(['version' => '1.2.3', 'data' => 'release data']);

        /** @var \Mockery\MockInterface|VCSProviderInterface $fetcher */
        $service = new ReleaseService($fetcher);

        // Assert that the correct release data is returned
        $result = $service->collectReleaseData('1.2.3');
        $this->assertEquals(['version' => '1.2.3', 'data' => 'release data'], $result);
    }

    /** @test */
    public function testFetchesTheLatestReleaseWhenNoVersionIsProvided()
    {
        /** @var \Mockery\MockInterface|VCSProviderInterface $fetcher */
        $fetcher = Mockery::mock(VCSProviderInterface::class);

        $fetcher->shouldReceive('getLatestRelease')
            ->once()
            ->andReturn(['version' => 'latest', 'data' => 'latest release data']);

        $service = new ReleaseService($fetcher);

        // Assert that the latest release data is returned
        $result = $service->collectReleaseData();
        $this->assertEquals(['version' => 'latest', 'data' => 'latest release data'], $result);
    }
}
