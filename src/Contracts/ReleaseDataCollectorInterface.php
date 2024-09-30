<?php

namespace AnisAronno\LaravelAutoUpdater\Contracts;

interface ReleaseDataCollectorInterface
{
    /**
     * Collect the release data for the given version.
     *
     * @param string|null $version
     * @return array
     */
    public function collectReleaseData(?string $version): array;
}
