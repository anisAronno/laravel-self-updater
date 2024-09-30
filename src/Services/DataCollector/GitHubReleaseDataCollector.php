<?php

namespace AnisAronno\LaravelAutoUpdater\Services\DataCollector;

use AnisAronno\LaravelAutoUpdater\Contracts\ReleaseDataCollectorInterface;
use AnisAronno\LaravelAutoUpdater\Services\ApiRequestService;

/**
<<<<<<<< HEAD:src/Services/DataCollector/GitLabReleaseDataCollector.php
 * Class GitLabReleaseDataCollector
 *
 * Fetch release data from GitLab.
 */
class GitLabReleaseDataCollector implements ReleaseDataCollectorInterface
========
 * Class GitHubReleaseDataCollector
 *
 * Fetch release data from GitHub.
 */
class GitHubReleaseDataCollector implements ReleaseDataCollectorInterface
>>>>>>>> develop:src/Services/DataCollector/GitHubReleaseDataCollector.php
{
    /**
     * Collect the release data for the given version.
     *
     * @param string|null $version The version to fetch (optional).
     * @return array The release data or an empty array on failure.
     */
    public function collectReleaseData(?string $version): array
    {
        $release_url = $this->buildRepoUrl($version);
        $response = ApiRequestService::get($release_url);

        if ($response->failed()) {
            return [];
        }

        return $this->extractReleaseData($response->json());
    }

    /**
     * Build the GitHub repository URL based on the version.
     *
     * @param string|null $version The version to fetch (optional).
     * @return string The repository URL.
     */
    protected function buildRepoUrl(?string $version): string
    {
        $baseRepoUrl = config('auto-updater.api_url');
        return $version
            ? "{$baseRepoUrl}/tags/v{$version}" // Fetch specific version
            : "{$baseRepoUrl}/latest";          // Fetch latest release if version not provided
    }

    /**
     * Extract release data from the API response.
     *
     * @param array $data The API response data.
     * @return array The formatted release data.
     */
    protected function extractReleaseData(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        return [
            'version'      => data_get($data, 'tag_name') ? ltrim(data_get($data, 'tag_name'), 'v') : null,
            'download_url' => data_get($data, 'zipball_url'),
            'changelog'    => data_get($data, 'body') ?? 'No changelog available',
        ];
    }

}
