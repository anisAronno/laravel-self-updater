<?php

namespace AnisAronno\LaravelAutoUpdater\Tests;

use AnisAronno\LaravelAutoUpdater\Services\ApiRequestService;
use AnisAronno\LaravelAutoUpdater\Services\VCSProvider\GitHubProvider;
use Mockery;

class GitHubProviderTest extends TestCase
{
    protected $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new GitHubProvider('https://github.com/anisAronno/laravel-starter');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testGetApiUrl(): void
    {
        $expected = 'https://api.github.com/repos/anisAronno/laravel-starter/releases';
        $this->assertEquals($expected, $this->provider->getApiUrl());
    }

    public function testGetLatestRelease(): void
    {
        $mockResponse = Mockery::mock('Illuminate\Http\Client\Response');
        $mockResponse->shouldReceive('failed')->andReturn(false);
        $mockResponse->shouldReceive('json')->andReturn([
            'tag_name' => 'v0.3.2',
            'zipball_url' => 'https://api.github.com/repos/anisAronno/laravel-starter/zipball/v0.3.2',
            'body' => 'Test changelog',
        ]);

        $mockApiRequestService = Mockery::mock('alias:'.ApiRequestService::class);
        $mockApiRequestService->shouldReceive('get')
            ->once()
            ->with('https://api.github.com/repos/anisAronno/laravel-starter/releases/latest')
            ->andReturn($mockResponse);

        $expected = [
            'version' => '0.3.2',
            'download_url' => 'https://api.github.com/repos/anisAronno/laravel-starter/zipball/v0.3.2',
            'changelog' => 'Test changelog',
        ];

        $this->assertEquals($expected, $this->provider->getLatestRelease());
    }

    public function testGetReleaseByVersion(): void
    {
        $mockResponse = Mockery::mock('Illuminate\Http\Client\Response');
        $mockResponse->shouldReceive('failed')->andReturn(false);
        $mockResponse->shouldReceive('json')->andReturn([
            'tag_name' => 'v0.3.2',
            'zipball_url' => 'https://api.github.com/repos/anisAronno/laravel-starter/zipball/v0.3.2',
            'body' => 'Test changelog',
        ]);

        $mockApiRequestService = Mockery::mock('alias:'.ApiRequestService::class);
        $mockApiRequestService->shouldReceive('get')
            ->once()
            ->with('https://api.github.com/repos/anisAronno/laravel-starter/releases/tags/v0.3.2')
            ->andReturn($mockResponse);

        $expected = [
            'version' => '0.3.2',
            'download_url' => 'https://api.github.com/repos/anisAronno/laravel-starter/zipball/v0.3.2',
            'changelog' => 'Test changelog',
        ];

        $this->assertEquals($expected, $this->provider->getReleaseByVersion('0.3.2'));
    }
}
