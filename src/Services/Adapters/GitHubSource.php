<?php

namespace AnisAronno\LaravelAutoUpdater\Services\Adapters;

use AnisAronno\LaravelAutoUpdater\Contracts\VersionSourceInterface;
use InvalidArgumentException;

/**
 * Class GitHubSource
 *
 * Implements the API URL logic for GitHub repositories.
 */
class GitHubSource implements VersionSourceInterface
{
    private $release_url;

    /**
     * GitHubSource constructor.
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
            throw new InvalidArgumentException("Invalid GitHub repository URL: {$this->release_url}");
        }

        list($user, $repo) = $parts;
        return sprintf('https://api.github.com/repos/%s/%s/releases', $user, $repo);
    }
}
