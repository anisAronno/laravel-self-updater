<?php

namespace AnisAronno\LaravelAutoUpdater\Services\VCSProvider;

/**
 * Class GitlabProvider
 *
 * VCS provider for GitLab.
 */
class GitlabProvider extends AbstractVCSProvider
{
    /**
     * Get the API URL.
     */
    public function getApiUrl(): string
    {
        [$user, $repo] = $this->extractUserAndRepo();

        return sprintf('https://gitlab.com/api/v4/projects/%s%%2F%s/repository/tags', urlencode($user), urlencode($repo));
    }

    /**
     * Extract the user and repository from the release URL.
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
     */
    protected function parseReleaseData(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $release = is_array($data[0]) ? $data[0] : $data;
        $projectPathArr = $this->extractUserAndRepo();
        $projectPath = implode('/', $projectPathArr);
        $version = data_get($release, 'name', '');

        return [
            'version' => $version,
            'download_url' => $this->getZipDownloadUrl($projectPath, $version),
            'changelog' => data_get($release, 'release.description', 'No changelog available'),
        ];
    }

    /**
     * Get GitLab repository download URL.
     *
     * @param  string  $projectPath  The GitLab project path.
     * @param  string  $version  The version (tag name) to download.
     * @return string The download URL.
     */
    protected function getZipDownloadUrl(string $projectPath, string $version): string
    {
        return sprintf('https://gitlab.com/%s/-/archive/%s/%s.zip', $projectPath, $version, $version);
    }
}
