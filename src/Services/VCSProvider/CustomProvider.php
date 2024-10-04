<?php

namespace AnisAronno\LaravelAutoUpdater\Services\VCSProvider;

use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Class CustomProvider
 *
 * Custom provider for custom repositories.
 */
class CustomProvider extends AbstractVCSProvider
{
    /**
     * The purchase key.
     */
    private ?string $purchaseKey;

    /**
     * CustomProvider constructor.
     */
    public function __construct(string $releaseUrl, ?string $purchaseKey)
    {
        parent::__construct($releaseUrl);
        $this->purchaseKey = $purchaseKey;
    }

    /**
     * Get the API URL.
     */
    protected function getApiUrl(): string
    {
        if (! filter_var($this->releaseUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Invalid custom API URL: {$this->releaseUrl}");
        }

        if ($this->purchaseKey) {
            return $this->releaseUrl.'?purchase_key='.urlencode($this->purchaseKey);
        }

        $parts = explode('/', parse_url($this->releaseUrl, PHP_URL_PATH));

        return sprintf('https://api.github.com/repos/%s/%s/releases', $parts[1], $parts[2]);
    }

    /**
     * Build the API URL.
     */
    protected function buildApiUrl(?string $version): string
    {
        return $this->getApiUrl();
    }

    /**
     * Parse the release data.
     *
     * @param  array  $data  The API response data.
     * @return array The formatted release data.
     */
    protected function parseReleaseData(array $data): array
    {
        return array_merge($data, [
            'version' => $data['version'] ? $data['version'] : null,
            'download_url' => $data['download_url'] ?? null,
            'changelog' => $data['changelog'] ?? 'No changelog available',
            'release_date' => ! empty($data['release_date']) ? Carbon::parse($data['release_date'])->format('d M, Y h:i:s a') : null,
        ]);
    }
}
