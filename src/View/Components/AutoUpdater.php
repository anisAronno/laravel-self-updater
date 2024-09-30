<?php

namespace AnisAronno\LaravelAutoUpdater\View\Components;

use Illuminate\View\Component;
use AnisAronno\LaravelAutoUpdater\Services\VersionService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\JsonResponse;
use Exception;

class AutoUpdater extends Component
{
    public $hasUpdate;
    public $currentVersion;
    public $latestVersion;
    public $changelog;
    public $error;

    protected $versionService;

    public function __construct(VersionService $versionService)
    {
        $this->versionService = $versionService;
        $this->refreshVersionData();
    }

    public function render()
    {
        return view('auto-updater::components.auto-updater');
    }

    protected function refreshVersionData(): void
    {
        try {
            $this->currentVersion = $this->versionService->getCurrentVersion();
            $latestRelease = $this->versionService->collectReleaseData();

            $this->latestVersion = $latestRelease['version'] ?? null;
            $this->changelog = $latestRelease['changelog'] ?? null;

            if ($this->currentVersion && $this->latestVersion) {
                $this->hasUpdate = version_compare($this->latestVersion, $this->currentVersion, '>');
            } else {
                $this->hasUpdate = false;
                $this->error = 'Unable to determine update status due to missing version information.';
            }
        } catch (Exception $e) {
            $this->error = 'Error fetching release data: ' . $e->getMessage();
            $this->hasUpdate = false;
        }
    }

    public function initiateUpdate(): JsonResponse
    {
        try {
            Artisan::call('update:initiate');
            return $this->jsonResponse(true, Artisan::output());
        } catch (Exception $e) {
            Artisan::call('up');
            return $this->jsonResponse(false, 'Error: ' . $e->getMessage());
        }
    }

    public function checkForUpdates(): JsonResponse
    {
        $this->refreshVersionData();

        if ($this->error) {
            return $this->jsonResponse(false, $this->error);
        }

        return $this->jsonResponse(true, 'Update check successful', [
            'current_version' => $this->currentVersion,
            'latest_version' => $this->latestVersion,
            'changelog' => $this->changelog,
            'has_update' => $this->hasUpdate
        ]);
    }

    protected function jsonResponse(bool $success, string $message, array $data = []): JsonResponse
    {
        return response()->json(array_merge(
            ['success' => $success, 'message' => $message],
            $data
        ));
    }
}
