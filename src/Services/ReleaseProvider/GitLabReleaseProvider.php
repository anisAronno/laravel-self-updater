<?php

namespace AnisAronno\LaravelAutoUpdater\Services\ReleaseProvider;

use AnisAronno\LaravelAutoUpdater\Contracts\ReleaseProviderInterface;
use InvalidArgumentException;

/**
 * Class GitLabReleaseProvider
 *
 * Release provider for GitLab repositories.
 */
class GitLabReleaseProvider implements ReleaseProviderInterface
{
    private string $release_url;

    /**
     * GitLabReleaseProvider constructor.
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
            throw new InvalidArgumentException("Invalid GitLab repository URL: {$this->release_url}");
        }

        list($user, $repo) = $parts;
        return sprintf('https://gitlab.com/api/v4/projects/%s%%2F%s/repository/tags', urlencode($user), urlencode($repo));
    }
}
