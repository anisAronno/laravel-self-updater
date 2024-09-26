<?php

namespace AnisAronno\LaravelAutoUpdater\Services\Adapters;

use AnisAronno\LaravelAutoUpdater\Contracts\VersionSourceInterface;
use InvalidArgumentException;

/**
 * Class CustomAdapter
 *
 * Implements the API URL logic for custom repositories.
 */
class CustomSource implements VersionSourceInterface
{
    private $release_url;
    private $purchaseKey;

    /**
     * CustomAdapter constructor.
     *
     * @param string $release_url
     * @param string|null $purchaseKey
     */
    public function __construct(string $release_url, ?string $purchaseKey)
    {
        $this->release_url = $release_url;
        $this->purchaseKey = $purchaseKey;
    }

    /**
     * Get the API URL for the repository.
     *
     * @return string
     */
    public function getApiUrl(): string
    {
        if (!filter_var($this->release_url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Invalid custom API URL: {$this->release_url}");
        }

        if ($this->purchaseKey) {
            return $this->release_url . '?purchase_key=' . urlencode($this->purchaseKey);
        }

        return $this->release_url;
    }
}
