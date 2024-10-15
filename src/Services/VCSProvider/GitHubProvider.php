<?php

namespace AnisAronno\LaravelAutoUpdater\Services\VCSProvider;

use AnisAronno\LaravelAutoUpdater\Services\ApiRequestService;
use Carbon\Carbon;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class GitHubProvider
 *
 * VCS provider for GitHub.
 */
class GitHubProvider extends AbstractVCSProvider
{
    /**
     * Get the API URL.
     */
    public function getApiUrl(): string
    {
        [$user, $repo] = $this->extractUserAndRepo();

        return sprintf('https://api.github.com/repos/%s/%s/releases', $user, $repo);
    }

    /**
     * Build the API URL.
     */
    protected function buildApiUrl(?string $version): string
    {
        $baseUrl = $this->getApiUrl();

        // GitHub tags may or may not have the 'v' prefix, so we try both.
        if ($version) {
            $strippedVersion = ltrim($version, 'v');
            $tagWithV = "{$baseUrl}/tags/v{$strippedVersion}";
            $tagWithoutV = "{$baseUrl}/tags/{$strippedVersion}";

            // Check which one exists first
            if ($this->tagExists($tagWithV)) {
                return $tagWithV;
            } elseif ($this->tagExists($tagWithoutV)) {
                return $tagWithoutV;
            }

            throw new InvalidArgumentException("Release version {$version} not found.");
        }

        return "{$baseUrl}/latest";
    }

    /**
     * Check if the tag exists using Guzzle HTTP client via ApiRequestService.
     */
    private function tagExists(string $url): bool
    {
        try {
            $response = ApiRequestService::get($url);

            return $response->getStatusCode() === 200;
        } catch (HttpException $e) {
            if ($e->getStatusCode() && $e->getStatusCode() === 404) {
                return false; // Tag does not exist
            }

            throw $e;
        }
    }

    /**
     * Parse the release data.
     *
     * @param  array  $data  The API response data.
     * @return array The formatted release data.
     */
    protected function parseReleaseData(array $data): array
    {
        return [
            'version' => $data['tag_name'],
            'download_url' => $data['zipball_url'] ?? '',
            'changelog' => $data['body'] ?? 'No changelog available',
            'release_date' => ! empty($data['created_at']) ? Carbon::parse($data['created_at'])->format('d M, Y h:i:s a') : null,
        ];
    }
}
