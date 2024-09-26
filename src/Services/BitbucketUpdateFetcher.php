<?php

namespace AnisAronno\LaravelAutoUpdater\Services;

use AnisAronno\LaravelAutoUpdater\Contracts\UpdateFetcherInterface;
use AnisAronno\LaravelAutoUpdater\Services\ApiRequestService;

/**
 * Class BitbucketUpdateFetcher
 * 
 * This class is responsible for fetching release data from Bitbucket.
 */
class BitbucketUpdateFetcher implements UpdateFetcherInterface
{
    /**
     * Fetch release data from Bitbucket.
     *
     * @param string|null $version The specific version to fetch (optional).
     * @return array The release data or an empty array on failure.
     */
    public function fetchReleaseData(?string $version): array
    {
        $release_url  = $this->buildRepoUrl($version);
        $response = ApiRequestService::get($release_url);

        if ($response->failed()) {
            return [];
        }

        return $this->extractReleaseData($response->json(), $version);
    }

    /**
     * Build the Bitbucket repository URL based on the version.
     *
     * @param string|null $version The version to fetch (optional).
     * @return string The repository URL.
     */
    protected function buildRepoUrl(?string $version): string
    {
        $baseRepoUrl = config('auto-updater.api_url');

        return $version
            ? "{$baseRepoUrl}/{$version}" // Fetch specific version
            : "{$baseRepoUrl}"; // Fetch all tags if version not provided
    }

    /**
     * Extract release data from the API response.
     *
     * @param array $data The API response data.
     * @param string|null $version The version to fetch.
     * @return array The formatted release data.
     */
    protected function extractReleaseData(array $data, ?string $version): array
    {
        // When fetching a specific version
        if (! empty($version)) {
            return $this->extractSingleReleaseData($data);
        }

        // When fetching all versions
        return $this->extractMultipleReleaseData($data);
    }

    /**
     * Extract data for a single release.
     *
     * @param array $data The API response data for a single version.
     * @return array The formatted release data.
     */
    protected function extractSingleReleaseData(array $data): array
    {
        return [
            'version'      => data_get($data, 'name'),
            'download_url' => $this->getZipDownloadUrl($this->parseProjectInfo(), data_get($data, 'name')),
            "changelog" => data_get($data, 'target.message', 'No changelog available'),
        ];
    }

    /**
     * Extract data for multiple releases.
     *
     * @param array $data The API response data for multiple versions.
     * @return array The formatted release data.
     */
    protected function extractMultipleReleaseData(array $data): array
    {
        if (empty($data['values']) || empty($data['values'][0])) {
            return [];
        }

        $latestRelease = end($data['values']);
        $projectInfo = $this->parseProjectInfo();

        return [
            'version' => data_get($latestRelease, 'name'),
            'download_url' => $this->getZipDownloadUrl($projectInfo, data_get($latestRelease, 'name')),
            "changelog" => data_get($latestRelease, 'message'),
        ];
    }

    /**
     * Parse the project information from the API URL.
     *
     * @return array The project information containing workspace and repo_slug.
     * @throws \Exception If unable to parse the project information.
     */
    protected function parseProjectInfo(): array
    {
        $apiUrl = config('auto-updater.api_url');
        $parts  = parse_url($apiUrl);
        $path   = explode('/', trim($parts['path'], '/'));

        // The project info should be after 'repositories' in the URL
        $repoIndex = array_search('repositories', $path);

        if ($repoIndex !== false && isset($path[$repoIndex + 1]) && isset($path[$repoIndex + 2])) {
            return [
                'workspace' => $path[$repoIndex + 1],
                'repo_slug' => $path[$repoIndex + 2],
            ];
        }

        throw new \Exception('Unable to parse project information from API URL');
    }

    /**
     * Get Bitbucket repository download URL.
     *
     * @param array $projectInfo The project information containing workspace and repo_slug.
     * @param string $version The version (tag name) to download.
     * @return string The download URL.
     */
    protected function getZipDownloadUrl(array $projectInfo, string $version): string
    {
        return "https://bitbucket.org/{$projectInfo['workspace']}/{$projectInfo['repo_slug']}/get/{$version}.zip";
    }
}
