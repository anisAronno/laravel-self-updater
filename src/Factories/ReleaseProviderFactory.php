<?php

namespace AnisAronno\LaravelAutoUpdater\Factories;

use AnisAronno\LaravelAutoUpdater\Contracts\ReleaseProviderInterface;
use AnisAronno\LaravelAutoUpdater\Services\ReleaseProvider\CustomReleaseProvider;
use AnisAronno\LaravelAutoUpdater\Services\ReleaseProvider\GitHubReleaseProvider;
use AnisAronno\LaravelAutoUpdater\Services\ReleaseProvider\GitLabReleaseProvider;
use AnisAronno\LaravelAutoUpdater\Services\ReleaseProvider\BitbucketReleaseProvider;

/**
 * Class ReleaseProviderFactory
 *
 * Factory class to create instances of the release provider.
 */
class ReleaseProviderFactory
{
    /**
     * Create an instance of the appropriate ReleaseProvider.
     *
     * @param string $release_url
     * @param string|null $purchaseKey
     * @return ReleaseProviderInterface
     */
    public static function create(string $release_url, ?string $purchaseKey): ReleaseProviderInterface
    {
        switch (true) {
            case self::isGitHubUrl($release_url):
                return new GitHubReleaseProvider($release_url);

            case self::isGitLabUrl($release_url):
                return new GitLabReleaseProvider($release_url);

            case self::isBitbucketUrl($release_url):
                return new BitbucketReleaseProvider($release_url);

            default:
                return new CustomReleaseProvider($release_url, $purchaseKey);
        }
    }

    /**
     * Check if the URL is a GitHub repository URL.
     *
     * @param string $url
     * @return bool
     */
    private static function isGitHubUrl(string $url): bool
    {
        return preg_match('/^https:\/\/github\.com\/([^\/]+)\/([^\/]+)$/', $url);
    }

    /**
     * Check if the URL is a GitLab repository URL.
     *
     * @param string $url
     * @return bool
     */
    private static function isGitLabUrl(string $url): bool
    {
        return preg_match('/^https:\/\/gitlab\.com\/([^\/]+)\/([^\/]+)$/', $url);
    }

    /**
     * Check if the URL is a Bitbucket repository URL.
     *
     * @param string $url
     * @return bool
     */
    private static function isBitbucketUrl(string $url): bool
    {
        return preg_match('/^https:\/\/bitbucket\.org\/([^\/]+)\/([^\/]+)$/', $url);
    }
}
