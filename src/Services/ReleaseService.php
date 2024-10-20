<?php

namespace AnisAronno\LaravelSelfUpdater\Services;

use AnisAronno\LaravelSelfUpdater\Contracts\VCSProviderInterface;
use Illuminate\Support\Facades\File;

/**
 * Class ReleaseService
 *
 * This class is responsible for fetching the current version of the project
 * and fetching release data from the repository.
 */
class ReleaseService
{
    protected VCSProviderInterface $fetcher;

    /**
     * ReleaseService constructor.
     */
    public function __construct(VCSProviderInterface $fetcher)
    {
        $this->fetcher = $fetcher;
    }

    /**
     * Get the current version of the project.
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
     */
    public function collectReleaseData(?string $version = null): array
    {
        if ($version) {
            return $this->fetcher->getReleaseByVersion($version);
        } else {
            return $this->fetcher->getLatestRelease();
        }
    }
}
