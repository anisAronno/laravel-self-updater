<?php

namespace AnisAronno\LaravelAutoUpdater\Factories;

use AnisAronno\LaravelAutoUpdater\Contracts\ReleaseDataCollectorInterface;
use AnisAronno\LaravelAutoUpdater\Services\DataCollector\GitHubReleaseDataCollector;
use AnisAronno\LaravelAutoUpdater\Services\DataCollector\GitLabReleaseDataCollector;
use AnisAronno\LaravelAutoUpdater\Services\DataCollector\BitbucketReleaseDataCollector;
use AnisAronno\LaravelAutoUpdater\Services\DataCollector\CustomUrlReleaseDataCollector;
use InvalidArgumentException;

/**
 * Class ReleaseDataCollectorFactory
 *
 * Factory class to create instances of the release data collector.
 */
class ReleaseDataCollectorFactory
{
    /**
     * Create a new instance of the release data collector.
     *
     * @param string $source
     * @return ReleaseDataCollectorInterface
     */
    public static function create(string $source): ReleaseDataCollectorInterface
    {
        switch ($source) {
            case 'github':
                return new GitHubReleaseDataCollector();
            case 'gitlab':
                return new GitLabReleaseDataCollector();
            case 'bitbucket':
                return new BitbucketReleaseDataCollector();
            case 'custom':
                return new CustomUrlReleaseDataCollector();
            default:
                throw new InvalidArgumentException("Invalid source [$source] provided.");
        }
    }
}