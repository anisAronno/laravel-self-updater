<?php

namespace AnisAronno\LaravelAutoUpdater\Contracts;

interface ReleaseProviderInterface
{
    /**
     * Get the API URL for the repository.
     *
     * @return string
     */
    public function getApiUrl(): string;
}
