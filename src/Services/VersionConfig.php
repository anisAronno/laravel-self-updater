<?php

namespace AnisAronno\LaravelAutoUpdater\Services;

use AnisAronno\LaravelAutoUpdater\Contracts\VersionSourceInterface;
use AnisAronno\LaravelAutoUpdater\Services\Adapters\GitLabSource;
use AnisAronno\LaravelAutoUpdater\Services\Adapters\GitHubSource;
use AnisAronno\LaravelAutoUpdater\Services\Adapters\BitbucketSource;

/**
 * Class VersionConfig
 *
 * Provides the configuration for the version source.
 */
class VersionConfig
{
    private $source;

    /**
     * VersionConfig constructor.
     *
     * @param VersionSourceInterface $source
     */
    public function __construct(VersionSourceInterface $source)
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
            case GitHubSource::class:
                return 'github';
            case GitLabSource::class:
                return 'gitlab';
            case BitbucketSource::class:
                return 'bitbucket';
            default:
                return 'custom';
        }
    }
}
