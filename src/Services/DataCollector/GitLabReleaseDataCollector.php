<?php

namespace AnisAronno\LaravelAutoUpdater\Services\DataCollector;

use AnisAronno\LaravelAutoUpdater\Services\ApiRequestService;
use AnisAronno\LaravelAutoUpdater\Contracts\ReleaseDataCollectorInterface;
use Exception;

/**
<<<<<<<< HEAD:src/Services/DataCollector/GitHubReleaseDataCollector.php
 * Class GitHubReleaseDataCollector
 *
 * Fetch release data from GitHub.
 */
class GitHubReleaseDataCollector implements ReleaseDataCollectorInterface
========
 * Class GitLabReleaseDataCollector
 *
 * Fetch release data from GitLab.
 */
class GitLabReleaseDataCollector implements ReleaseDataCollectorInterface
>>>>>>>> develop:src/Services/DataCollector/GitLabReleaseDataCollector.php
{
    /**
     * Collect the release data for the given version.
     *
     * @param string|null $version The version to fetch (optional).
     * @return array The release data or an empty array on failure.
     * @throws Exception
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
     * Build the GitLab repository URL based on the version.
     *
     * @param string|null $version The specific version to fetch (optional).
     * @return string The repository URL.
     */
    protected function buildRepoUrl(?string $version): string
    {
        $baseRepoUrl = config('auto-updater.api_url');

        return $version
            ? "{$baseRepoUrl}/{$version}" // Fetch specific version
            : $baseRepoUrl; // Fetch all tags if version not provided
    }

    /**
     * Extract release data from the API response.
     *
     * @param array $data The API response data.
     * @return array The formatted release data.
     * @throws Exception
     */
    protected function extractReleaseData(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $latestRelease = is_array($data[0]) ? $data[0] : $data;

        $version = data_get($latestRelease, 'name', null);
        $projectPath = $this->parseProjectPath();

        return [
            'version'      => $version,
            'download_url' => $this->getZipDownloadUrl($projectPath, $version),
            'changelog'    => data_get($latestRelease, 'body', 'No changelog available'),
        ];
    }


    /**
     * Parse the project path from the API URL.
     *
     * @return string The project path.
     * @throws Exception If unable to parse the project path.
     */
    protected function parseProjectPath(): string
    {
        $apiUrl = config('auto-updater.api_url');

        $parts = parse_url($apiUrl);
        $path = trim($parts['path'], '/');
        $pathParts = explode('/', $path);

        // GitHub URL format: api.github.com/repos/username/repository
        if (strpos($apiUrl, 'api.github.com') !== false) {
            if (count($pathParts) >= 3 && $pathParts[0] === 'repos') {
                return $pathParts[1] . '/' . $pathParts[2];
            }
        }

        throw new Exception("Unable to parse project path from API URL: $apiUrl");
    }

    /**
     * Get GitLab repository download URL.
     *
     * @param string $projectPath The GitLab project path.
     * @param string $version The version (tag name) to download.
     * @return string The download URL.
     */
    protected function getZipDownloadUrl(string $projectPath, string $version): string
    {
        return "https://gitlab.com/{$projectPath}/-/archive/{$version}/-{$version}.zip";
    }
}
