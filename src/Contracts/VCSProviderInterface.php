<?php

namespace AnisAronno\LaravelAutoUpdater\Contracts;

/**
 * Interface VCSProviderInterface
 *
 * Interface for the VCS provider.
 */
interface VCSProviderInterface
{
    /**
     * Get the latest release.
     *
     * @return array
     */
    public function getLatestRelease(): array;

    /**
     * Get the release by version.
     *
     * @param string $version
     *
     * @return array
     */
    public function getReleaseByVersion(string $version): array;
}
