<?php

namespace AnisAronno\LaravelAutoUpdater\Tests;

use AnisAronno\LaravelAutoUpdater\Services\ApiRequestService;
use AnisAronno\LaravelAutoUpdater\Services\VCSProvider\GitlabProvider;
use Mockery;
use Orchestra\Testbench\TestCase;

class GitLabProviderTest extends TestCase
{
    protected $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new GitlabProvider('https://gitlab.com/anis-aleshatech/laravel-starter');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testGetApiUrl(): void
    {
        $expected = 'https://gitlab.com/api/v4/projects/anis-aleshatech%2Flaravel-starter/repository/tags';
        $this->assertEquals($expected, $this->provider->getApiUrl());
    }

    public function testGetLatestRelease(): void
    {
        $mockResponse = Mockery::mock('Illuminate\Http\Client\Response');
        $mockResponse->shouldReceive('failed')->andReturn(false);
        $mockResponse->shouldReceive('json')->andReturn([
            [
                'name' => 'v0.3.1',
                'release' => [
                    'description' => 'Test changelog',
                ],
            ],
        ]);

        $mockApiRequestService = Mockery::mock('alias:' . ApiRequestService::class);
        $mockApiRequestService->shouldReceive('get')->once()->with('https://gitlab.com/api/v4/projects/anis-aleshatech%2Flaravel-starter/repository/tags')->andReturn($mockResponse);

        $expected = [
            'version' => 'v0.3.1',
            'download_url' => 'https://gitlab.com/anis-aleshatech/laravel-starter/-/archive/v0.3.1/v0.3.1.zip',
            'changelog' => 'Test changelog',
        ];

        $this->assertEquals($expected, $this->provider->getLatestRelease());
    }

    public function testGetReleaseByVersion(): void
    {
        $mockResponse = Mockery::mock('Illuminate\Http\Client\Response');
        $mockResponse->shouldReceive('failed')->andReturn(false);
        $mockResponse->shouldReceive('json')->andReturn([
            [
                'name' => 'v0.3.1',
                'release' => [
                    'description' => 'Test changelog',
                ],
            ],
        ]);

        $mockApiRequestService = Mockery::mock('alias:' . ApiRequestService::class);
        $mockApiRequestService->shouldReceive('get')->once()->with('https://gitlab.com/api/v4/projects/anis-aleshatech%2Flaravel-starter/repository/tags/v0.3.1')->andReturn($mockResponse);

        $expected = [
            'version' => 'v0.3.1',
            'download_url' => 'https://gitlab.com/anis-aleshatech/laravel-starter/-/archive/v0.3.1/v0.3.1.zip',
            'changelog' => 'Test changelog',
        ];

        $this->assertEquals($expected, $this->provider->getReleaseByVersion('v0.3.1'));
    }
}
