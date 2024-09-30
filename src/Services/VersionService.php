<?php

namespace AnisAronno\LaravelAutoUpdater\Services;

use AnisAronno\LaravelAutoUpdater\Contracts\ReleaseDataCollectorInterface;
use Illuminate\Support\Facades\File;

/**
 * Class VersionService
 *
 * This class is responsible for fetching the current version of the project
 * and fetching release data from the repository.
 */
class VersionService
{
    protected ReleaseDataCollectorInterface $fetcher;

    /**
     * VersionService constructor.
     *
     * @param ReleaseDataCollectorInterface $fetcher
     */
    public function __construct(ReleaseDataCollectorInterface $fetcher)
    {
        $this->fetcher = $fetcher;
    }

    /**
     * Get the current version of the project.
     *
     * @return string
     */
    public function getCurrentVersion(): string
    {
        $composerFile = base_path('composer.json');

        if (File::exists($composerFile)) {
            $composerContent = json_decode(File::get($composerFile), true);

            return $composerContent['version'] ?? '0.0.0';
        }

        return '0.0.0';
    }

    /**
     * Fetch release data from the repository.
     *
     * @param string|null $version
     * @return array
     */
    public function collectReleaseData(string $version = null): array
    {
        return $this->fetcher->collectReleaseData($version);
    }
}
