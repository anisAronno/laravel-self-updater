<?php

namespace AnisAronno\LaravelAutoUpdater\Services;

use AnisAronno\LaravelAutoUpdater\Contracts\ReleaseProviderInterface;
use AnisAronno\LaravelAutoUpdater\Services\ReleaseProvider\GitLabReleaseProvider;
use AnisAronno\LaravelAutoUpdater\Services\ReleaseProvider\GitHubReleaseProvider;
use AnisAronno\LaravelAutoUpdater\Services\ReleaseProvider\BitbucketReleaseProvider;

/**
 * Class ReleaseProviderConfig
 *
 * Configuration class for the version source.
 */
class ReleaseProviderConfig
{
    private ReleaseProviderInterface $source;

    /**
     * ReleaseProviderConfig constructor.
     *
     * @param ReleaseProviderInterface $source
     */
    public function __construct(ReleaseProviderInterface $source)
    {
        $this->source = $source;
    }

    /**
     * Get the configuration for the version source.
     *
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'api_url' => $this->source->getApiUrl(),
            'source' => $this->getSourceType(),
        ];
    }

    /**
     * Get the type of the version source.
     *
     * @return string
     */
    private function getSourceType(): string
    {
        switch (get_class($this->source)) {
            case GitHubReleaseProvider::class:
                return 'github';
            case GitLabReleaseProvider::class:
                return 'gitlab';
            case BitbucketReleaseProvider::class:
                return 'bitbucket';
            default:
                return 'custom';
        }
    }
}
