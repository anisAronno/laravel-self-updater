<?php

namespace AnisAronno\LaravelAutoUpdater\Contracts;

interface VersionSourceInterface
{
    /**
     * Get the API URL for the repository.
     *
     * @return string
     */
    public function getApiUrl(): string;
}
