<?php

namespace AnisAronno\LaravelAutoUpdater\Tests;

use AnisAronno\LaravelAutoUpdater\Services\ReleaseService;
use AnisAronno\LaravelAutoUpdater\View\Components\AutoUpdater;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Mockery;

class AutoUpdaterComponentTest extends TestCase
{
    /**
     * @var \Mockery\MockInterface|ReleaseService
     */
    protected $releaseService;

    /**
     * @var AutoUpdater
     */
    protected $component;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var ReleaseService|Mockery\MockInterface $releaseService */
        $this->releaseService = Mockery::mock(ReleaseService::class);
        $this->app->instance(ReleaseService::class, $this->releaseService);

        $this->component = new AutoUpdater($this->releaseService);
    }

    public function testRetrieveVersionDataWhenUpdateAvailable()
    {
        $this->releaseService->shouldReceive('getCurrentVersion')->andReturn('1.0.0');
        $this->releaseService->shouldReceive('collectReleaseData')->andReturn([
            'version' => '1.1.0',
            'changelog' => 'New features added',
        ]);

        Cache::shouldReceive('has')->andReturn(false);
        Cache::shouldReceive('put')->once();

        $versionData = $this->invokePrivateMethod($this->component, 'retrieveVersionData');

        $this->assertEquals('1.0.0', $versionData['currentVersion']);
        $this->assertEquals('1.1.0', $versionData['latestVersion']);
        $this->assertEquals('New features added', $versionData['changelog']);
        $this->assertTrue($versionData['hasUpdate']);
    }

    public function testRetrieveVersionDataWhenUpToDate()
    {
        $this->releaseService->shouldReceive('getCurrentVersion')->andReturn('1.1.0');
        $this->releaseService->shouldReceive('collectReleaseData')->andReturn([
            'version' => '1.1.0',
            'changelog' => 'No new changes',
        ]);

        Cache::shouldReceive('has')->andReturn(false);
        Cache::shouldReceive('put')->once();

        $versionData = $this->invokePrivateMethod($this->component, 'retrieveVersionData');

        $this->assertEquals('1.1.0', $versionData['currentVersion']);
        $this->assertEquals('1.1.0', $versionData['latestVersion']);
        $this->assertEquals('No new changes', $versionData['changelog']);
        $this->assertFalse($versionData['hasUpdate']);
    }

    public function testRetrieveVersionDataFromCache()
    {
        $cachedData = [
            'currentVersion' => '1.0.0',
            'latestVersion' => '1.1.0',
            'changelog' => 'Cached changelog',
            'hasUpdate' => true,
        ];

        Cache::shouldReceive('has')->andReturn(true);
        Cache::shouldReceive('get')->andReturn($cachedData);

        $versionData = $this->invokePrivateMethod($this->component, 'retrieveVersionData');

        $this->assertEquals($cachedData, $versionData);
    }

    public function initiateSystemUpdate(): JsonResponse
    {
        try {
            $latestRelease = $this->releaseService->collectReleaseData();

            if (isset($latestRelease['download_url'])) {
                Artisan::call('update:initiate');

                return $this->createJsonResponse(true, 'Update initiated successfully.');
            } else {
                return $this->createJsonResponse(false, 'Update failed: Missing download URL.');
            }
        } catch (Exception $e) {
            return $this->createJsonResponse(false, "Update failed: {$e->getMessage()}");
        }
    }

    protected function createJsonResponse(bool $success, string $message): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }

    public function testCheckForSystemUpdates()
    {
        $this->releaseService->shouldReceive('getCurrentVersion')->andReturn('1.0.0');
        $this->releaseService->shouldReceive('collectReleaseData')->andReturn([
            'version' => '1.1.0',
            'changelog' => 'New features added',
        ]);

        Cache::shouldReceive('has')->andReturn(false);
        Cache::shouldReceive('put')->once();

        $response = $this->component->checkForSystemUpdates();

        $this->assertTrue($response->getData()->success);
        $this->assertEquals('Data refreshed successfully.', $response->getData()->message);
        $this->assertTrue($response->getData()->hasUpdate);
    }

    protected function invokePrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
