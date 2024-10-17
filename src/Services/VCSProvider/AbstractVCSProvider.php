<?php

namespace AnisAronno\LaravelSelfUpdater\Services\VCSProvider;

use AnisAronno\LaravelSelfUpdater\Contracts\VCSProviderInterface;
use AnisAronno\LaravelSelfUpdater\Services\ApiRequestService;
use InvalidArgumentException;

/**
 * Class AbstractVCSProvider
 *
 * Abstract class for the VCS provider.
 */
abstract class AbstractVCSProvider implements VCSProviderInterface
{
    /**
     * The release URL.
     */
    protected string $releaseUrl;

    /**
     * AbstractVCSProvider constructor.
     */
    public function __construct(string $releaseUrl)
    {
        $this->releaseUrl = $releaseUrl;
    }

    /**
     * Get the API URL.
     */
    abstract protected function getApiUrl(): string;

    /**
     * Parse the release data.
     *
     * @param  array  $data  The API response data.
     * @return array The formatted release data.
     */
    abstract protected function parseReleaseData(array $data): array;

    /**
     * Fetch the release data.
     *
     * @param  string|null  $version  The release version.
     * @return array The release data.
     *
     * @throws InvalidArgumentException If an error occurs.
     */
    protected function fetchReleaseData(?string $version = null): array
    {
        $url = $this->buildApiUrl($version);
        $response = $this->makeApiRequest($url);

        if (empty($response)) {
            throw new InvalidArgumentException('Failed to fetch release data.');
        }

        return $this->parseReleaseData($response);
    }

    /**
     * Build the API URL.
     */
    abstract protected function buildApiUrl(?string $version): string;

    /**
     * Make an API request.
     */
    protected function makeApiRequest(string $url): array
    {
        $response = ApiRequestService::get($url);

        if ($response->failed()) {
            return [];
        }

        return $response->json();
    }

    /**
     * Get the latest release.
     */
    public function getLatestRelease(): array
    {
        return $this->fetchReleaseData();
    }

    /**
     * Get the release by version.
     */
    public function getReleaseByVersion(string $version): array
    {
        return $this->fetchReleaseData($version);
    }

    /**
     * Extract the username and repository name from the release URL.
     *
     * @return array The username and repository name.
     *
     * @throws \InvalidArgumentException If the release URL is invalid.
     */
    protected function extractUserAndRepo(): array
    {
        $parsedUrl = parse_url($this->releaseUrl);
        $path = trim($parsedUrl['path'], '/');
        $parts = explode('/', $path);

        if (count($parts) !== 2) {
            throw new InvalidArgumentException("Invalid repository URL: {$this->releaseUrl}");
        }

        return $parts;
    }
}
