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
     */
    public function getLatestRelease(): array;

    /**
     * Get the release by version.
     */
    public function getReleaseByVersion(string $version): array;
}
