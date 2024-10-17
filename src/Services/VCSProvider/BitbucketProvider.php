<?php

namespace AnisAronno\LaravelSelfUpdater\Services\VCSProvider;

use Carbon\Carbon;
use Exception;

/**
 * Class BitbucketProvider
 *
 * VCS provider for Bitbucket.
 */
class BitbucketProvider extends AbstractVCSProvider
{
    /**
     * Get the API URL.
     */
    public function getApiUrl(): string
    {
        [$user, $repo] = $this->extractUserAndRepo();

        return sprintf('https://api.bitbucket.org/2.0/repositories/%s/%s/refs/tags', $user, $repo);
    }

    /**
     * Build the API URL.
     */
    protected function buildApiUrl(?string $version): string
    {
        $baseUrl = $this->getApiUrl();

        return $version ? "{$baseUrl}/{$version}" : $baseUrl;
    }

    /**
     * Parse the release data.
     *
     * @param  array  $data  The API response data.
     * @return array The formatted release data.
     *
     * @throws Exception
     */
    protected function parseReleaseData(array $data): array
    {
        // Handling when fetching a specific version or all versions
        if (isset($data['values'][0])) {
            return $this->extractMultipleReleaseData($data);
        } else {
            return $this->extractSingleReleaseData($data);
        }
    }

    /**
     * Extract data for a single release.
     *
     * @param  array  $data  The API response data for a single version.
     * @return array The formatted release data.
     */
    protected function extractSingleReleaseData(array $data): array
    {
        [$workspace, $repo_slug] = $this->extractUserAndRepo();
        $repositoryParseData = compact('workspace', 'repo_slug');
        $releaseDate = data_get($data, 'target.date', null);

        return [
            'version' => data_get($data, 'name'),
            'download_url' => $this->getZipDownloadUrl($repositoryParseData, data_get($data, 'name')),
            'changelog' => data_get($data, 'target.message', 'No changelog available'),
            'release_date' => ! empty($releaseDate) ? Carbon::parse($releaseDate)->format('d M, Y h:i:s a') : null,
        ];
    }

    /**
     * Extract data for multiple releases.
     *
     * @param  array  $data  The API response data for multiple versions.
     * @return array The formatted release data.
     *
     * @throws Exception
     */
    protected function extractMultipleReleaseData(array $data): array
    {
        if (empty($data['values']) || empty($data['values'][0])) {
            return [];
        }

        $latestRelease = end($data['values']);

        [$workspace, $repo_slug] = $this->extractUserAndRepo();
        $repositoryParseData = compact('workspace', 'repo_slug');

        return [
            'version' => data_get($latestRelease, 'name'),
            'download_url' => $this->getZipDownloadUrl($repositoryParseData, data_get($latestRelease, 'name')),
            'changelog' => data_get($latestRelease, 'message', 'Changelog not available via Bitbucket API'),
        ];
    }

    /**
     * Get Bitbucket repository download URL.
     *
     * @param  array  $repositoryParseData  The project information containing workspace and repo_slug.
     * @param  string  $version  The version (tag name) to download.
     * @return string The download URL.
     */
    protected function getZipDownloadUrl(array $repositoryParseData, string $version): string
    {
        return sprintf('https://bitbucket.org/%s/%s/get/%s.zip', $repositoryParseData['workspace'], $repositoryParseData['repo_slug'], $version);
    }
}
