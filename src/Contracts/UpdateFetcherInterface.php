<?php

namespace AnisAronno\LaravelAutoUpdater\Contracts;

interface UpdateFetcherInterface
{
    /**
     * Fetch release data from the repository.
     *
     * @param string|null $version The specific version to fetch (optional).
     * @return array The release data or an empty array on failure.
     */
    public function fetchReleaseData(?string $version): array;
}
