<?php

namespace AnisAronno\LaravelAutoUpdater\Services;

use AnisAronno\LaravelAutoUpdater\Contracts\UpdateFetcherInterface;
use AnisAronno\LaravelAutoUpdater\Services\ApiRequestService;

/**
 * Class GitLabUpdateFetcher
 * 
 * This class is responsible for fetching release data from GitLab.
 */
class GitLabUpdateFetcher implements UpdateFetcherInterface
{
    /**
     * Fetch release data from GitLab.
     *
     * @param string|null $version The specific version to fetch (optional).
     * @return array The release data or an empty array on failure.
     */
    public function fetchReleaseData(?string $version): array
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
            'changelog'    => data_get($latestRelease, 'release.description', 'No changelog available'),
        ];
    }

    /**
     * Parse the project path from the API URL.
     *
     * @return string The project path.
     * @throws \Exception If unable to parse the project path.
     */
    protected function parseProjectPath(): string
    {
        $apiUrl = config('auto-updater.api_url');
        $parts = parse_url($apiUrl);
        $path = explode('/', trim($parts['path'], '/'));

        // The project path should be the part after 'projects' in the URL
        $projectIndex = array_search('projects', $path);
        if ($projectIndex !== false && isset($path[$projectIndex + 1])) {
            return urldecode($path[$projectIndex + 1]);
        }

        throw new \Exception("Unable to parse project path from API URL");
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
