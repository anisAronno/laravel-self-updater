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
            'release_date' => date('Y-m-d', strtotime('yesterday')),
        ]);

        $mockApiRequestService = Mockery::mock('alias:'.ApiRequestService::class);
        $mockApiRequestService->shouldReceive('get')->once()->with('https://api.github.com/repos/anisAronno/laravel-starter/releases/latest')->andReturn($mockResponse);

        $expected = [
            'version' => 'v0.3.2',
            'download_url' => 'https://api.github.com/repos/anisAronno/laravel-starter/zipball/v0.3.2',
            'changelog' => 'Test changelog',
            'release_date' => null,
        ];

        $this->assertEquals($expected, $this->provider->getLatestRelease());
    }

    public function testGetReleaseByVersion(): void
    {
        $mockResponse = Mockery::mock('Illuminate\Http\Client\Response');

        // Mock the expected methods on the response
        $mockResponse->shouldReceive('getStatusCode')->andReturn(200); // Status code check
        $mockResponse->shouldReceive('failed')->andReturn(false); // Failure check
        $mockResponse->shouldReceive('json')->andReturn([
            'tag_name' => 'v0.3.2',
            'zipball_url' => 'https://api.github.com/repos/anisAronno/laravel-starter/zipball/v0.3.2',
            'body' => 'Test changelog',
            'release_date' => date('Y-m-d', strtotime('yesterday')),
        ]);

        $mockApiRequestService = Mockery::mock('alias:'.ApiRequestService::class);

        // Ensure ApiRequestService get method is mocked properly
        $mockApiRequestService
            ->shouldReceive('get')
            ->twice() // Adjust if there are 2 calls
            ->with('https://api.github.com/repos/anisAronno/laravel-starter/releases/tags/v0.3.2')
            ->andReturn($mockResponse);

        $expected = [
            'version' => 'v0.3.2',
            'download_url' => 'https://api.github.com/repos/anisAronno/laravel-starter/zipball/v0.3.2',
            'changelog' => 'Test changelog',
            'release_date' => null,
        ];

        $this->assertEquals($expected, $this->provider->getReleaseByVersion('0.3.2'));
    }
}
