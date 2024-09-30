<?php

namespace AnisAronno\LaravelAutoUpdater\Services\Adapters;

use AnisAronno\LaravelAutoUpdater\Contracts\VersionSourceInterface;
use InvalidArgumentException;

/**
 * Class BitbucketSource
 *
 * Implements the API URL logic for Bitbucket repositories.
 */
class BitbucketSource implements VersionSourceInterface
{
    private string $release_url;

    /**
     * BitbucketSource constructor.
     *
     * @param string $release_url
     */
    public function __construct(string $release_url)
    {
        $this->release_url = $release_url;
    }

    /**
     * Get the API URL for the repository.
     *
     * @return string
     */
    public function getApiUrl(): string
    {
        $parsedUrl = parse_url($this->release_url);
        $path = trim($parsedUrl['path'], '/');
        $parts = explode('/', $path);

        if (count($parts) !== 2) {
            throw new InvalidArgumentException("Invalid Bitbucket repository URL: {$this->release_url}");
        }

        list($user, $repo) = $parts;
        return sprintf('https://api.bitbucket.org/2.0/repositories/%s/%s/refs/tags', $user, $repo);
    }
}
