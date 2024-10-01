<?php

namespace AnisAronno\LaravelAutoUpdater\Services\VCSProvider;

use AnisAronno\LaravelAutoUpdater\Contracts\VCSProviderInterface;

/**
 * Class VCSFactory
 *
 * Factory class for creating VCS providers.
 */
class VCSFactory
{
    /**
     * Create a VCS provider instance.
     *
     * @param string $releaseUrl The release URL.
     * @param string|null $purchaseKey The purchase key.
     * @return VCSProviderInterface
     */
    public static function create(string $releaseUrl, ?string $purchaseKey): VCSProviderInterface
    {
        if (strpos($releaseUrl, 'github.com') !== false) {
            return new GitHubProvider($releaseUrl);
        } elseif (strpos($releaseUrl, 'gitlab.com') !== false) {
            return new GitLabProvider($releaseUrl);
        } elseif (strpos($releaseUrl, 'bitbucket.org') !== false) {
            return new BitbucketProvider($releaseUrl);
        } else {
            return new CustomProvider($releaseUrl, $purchaseKey);
        }
    }
}
