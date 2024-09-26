<?php

namespace AnisAronno\LaravelAutoUpdater\Services;

use AnisAronno\LaravelAutoUpdater\Contracts\VersionSourceInterface;
use AnisAronno\LaravelAutoUpdater\Contracts\UpdateFetcherInterface;
use AnisAronno\LaravelAutoUpdater\Services\CustomUrlUpdateFetcher;
use AnisAronno\LaravelAutoUpdater\Services\GitHubUpdateFetcher;
use AnisAronno\LaravelAutoUpdater\Services\GitLabUpdateFetcher;
use AnisAronno\LaravelAutoUpdater\Services\BitbucketUpdateFetcher;
use AnisAronno\LaravelAutoUpdater\Services\Adapters\CustomSource;
use AnisAronno\LaravelAutoUpdater\Services\Adapters\GitHubSource;
use AnisAronno\LaravelAutoUpdater\Services\Adapters\GitLabSource;
use AnisAronno\LaravelAutoUpdater\Services\Adapters\BitbucketSource;

/**
 * Class VersionSourceFactory
 * Factory class to create the appropriate repository adapter based on the URL.
 */
class VersionSourceFactory
{
    /**
     * Create an instance of the appropriate UpdateFetcher.
     *
     * @param string $source
     * @return UpdateFetcherInterface
     * @throws \InvalidArgumentException
     */
    public static function createFetcher(string $source): UpdateFetcherInterface
    {
        switch ($source) {
            case 'github':
                return new GitHubUpdateFetcher();

            case 'gitlab':
                return new GitLabUpdateFetcher();

            case 'bitbucket':
                return new BitbucketUpdateFetcher();

            case 'custom':
                return new CustomUrlUpdateFetcher();
            default:
                throw new \InvalidArgumentException("Invalid source [$source] provided.");
        }
    }

    /**
     * Create an instance of the appropriate RepositoryAdapter.
     *
     * @param string $release_url
     * @param string|null $purchaseKey
     * @return VersionSourceInterface
     */
    public static function createSource(string $release_url, ?string $purchaseKey): VersionSourceInterface
    {
        switch (true) {
            case self::isGitHubUrl($release_url):
                return new GitHubSource($release_url);

            case self::isGitLabUrl($release_url):
                return new GitLabSource($release_url);

            case self::isBitbucketUrl($release_url):
                return new BitbucketSource($release_url);
            default:
                return new CustomSource($release_url, $purchaseKey);
        }
    }

    /**
     * Check if the URL is a GitHub repository URL.
     *
     * @param string $url
     * @return bool
     */
    private static function isGitHubUrl($url): bool
    {
        return preg_match('/^https:\/\/github\.com\/([^\/]+)\/([^\/]+)$/', $url);
    }

    /**
     * Check if the URL is a GitLab repository URL.
     *
     * @param string $url
     * @return bool
     */
    private static function isGitLabUrl($url): bool
    {
        return preg_match('/^https:\/\/gitlab\.com\/([^\/]+)\/([^\/]+)$/', $url);
    }

    /**
     * Check if the URL is a Bitbucket repository URL.
     *
     * @param string $url
     * @return bool
     */
    private static function isBitbucketUrl($url): bool
    {
        return preg_match('/^https:\/\/bitbucket\.org\/([^\/]+)\/([^\/]+)$/', $url);
    }
}
