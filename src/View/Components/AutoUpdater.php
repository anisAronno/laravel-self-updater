<?php

namespace AnisAronno\LaravelAutoUpdater\View\Components;

use AnisAronno\LaravelAutoUpdater\Services\ReleaseService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;

class AutoUpdater extends Component
{
    private const CACHE_KEY = 'auto_updater_data';

    private const CACHE_DURATION_IN_SECONDS = 600; // 10 minutes

    private ReleaseService $releaseService;

    private array $versionData;

    /**
     * Create a new component instance.
     */
    public function __construct(ReleaseService $releaseService)
    {
        $this->releaseService = $releaseService;
        $this->versionData = $this->retrieveVersionData();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('auto-updater::components.auto-updater', $this->versionData);
    }

    /**
     * Check if a new version is available.
     */
    public function initiateSystemUpdate(): JsonResponse
    {
        try {
            Artisan::call('update:initiate');

            return $this->createJsonResponse(true, Artisan::output());
        } catch (Exception $e) {
            Artisan::call('up');

            return $this->createJsonResponse(false, "Error: {$e->getMessage()}");
        }
    }

    /**
     * Check for system updates.
     */
    public function checkForSystemUpdates(): JsonResponse
    {
        $this->versionData = $this->retrieveVersionData(true);
        if (! empty($this->versionData['error'])) {
            return $this->createJsonResponse(false, $this->versionData['error']);
        }

        return $this->createJsonResponse(true, 'Data refreshed successfully.', $this->versionData);
    }

    /**
     * Retrieve version data from cache or fetch it from the repository.
     */
    private function retrieveVersionData(bool $forceRefresh = false): array
    {
        if (! $forceRefresh && $this->isVersionDataCached()) {
            return $this->getVersionDataFromCache();
        }

        return $this->fetchAndStoreVersionData();
    }

    /**
     * Check if version data is cached.
     */
    private function isVersionDataCached(): bool
    {
        return Cache::has(self::CACHE_KEY);
    }

    /**
     * Get version data from cache.
     */
    private function getVersionDataFromCache(): array
    {
        return Cache::get(self::CACHE_KEY);
    }

    /**
     * Fetch and store version data.
     */
    private function fetchAndStoreVersionData(): array
    {
        try {
            $versionData = $this->fetchLatestVersionData();
            $this->storeVersionDataInCache($versionData);

            return $versionData;
        } catch (Exception $e) {
            return $this->createErrorResponse($e);
        }
    }

    /**
     * Fetch the latest version data.
     */
    private function fetchLatestVersionData(): array
    {
        $currentVersion = $this->releaseService->getCurrentVersion();
        $latestRelease = $this->releaseService->collectReleaseData();

        return $this->compareAndStructureVersionData($currentVersion, $latestRelease);
    }

    /**
     * Compare and structure version data.
     */
    private function compareAndStructureVersionData(string $currentVersion, array $latestRelease): array
    {
        $versionData = [
            'currentVersion' => $currentVersion,
            'latestVersion' => ! empty($latestRelease['version']) ? ltrim($latestRelease['version'], 'v') : null,
            'changelog' => $latestRelease['changelog'] ?? null,
            'releaseDate' => $latestRelease['release_date'] ?? null,
            'hasUpdate' => false,
            'error' => null,
        ];

        if ($versionData['currentVersion'] && $versionData['latestVersion']) {
            $versionData['hasUpdate'] = $this->isNewVersionAvailable($versionData['latestVersion'], $versionData['currentVersion']);
        } else {
            $versionData['error'] = 'Unable to determine update status due to missing version information.';
        }

        return $versionData;
    }

    /**
     * Check if a new version is available.
     */
    private function isNewVersionAvailable(string $currentVersion, string $latestVersion): bool
    {
        return version_compare($currentVersion, $latestVersion, '>');
    }

    /**
     * Store version data in cache.
     */
    private function storeVersionDataInCache(array $versionData): void
    {
        Cache::put(self::CACHE_KEY, $versionData, self::CACHE_DURATION_IN_SECONDS);
    }

    /**
     * Create an error response.
     */
    private function createErrorResponse(Exception $e): array
    {
        return [
            'hasUpdate' => false,
            'error' => "Error fetching release data: {$e->getMessage()}",
        ];
    }

    /**
     * Create a JSON response.
     */
    private function createJsonResponse(bool $success, string $message, array $data = []): JsonResponse
    {
        return response()->json(array_merge(
            ['success' => $success, 'message' => $message],
            $data
        ));
    }
}
