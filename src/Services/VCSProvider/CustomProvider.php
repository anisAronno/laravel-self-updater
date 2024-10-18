<?php

namespace AnisAronno\LaravelSelfUpdater\Services\VCSProvider;

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
    public function __construct(string $releaseUrl)
    {
        parent::__construct($releaseUrl);
        $this->purchaseKey = $this->retrievePurchaseKey();
    }

    /**
     * Retrieve the purchase key from the environment or configuration.
     *
     * @throws InvalidArgumentException
     */
    private function retrievePurchaseKey(): ?string
    {
        $key = config('self-updater.license_key');

        if (! empty($key) && ! is_string($key)) {
            throw new InvalidArgumentException('Invalid purchase key format.');
        }

        return $key;
    }

    /**
     * Get the API URL.
     */
    protected function getApiUrl(): string
    {
        if ($this->purchaseKey) {
            return $this->releaseUrl . '?license_key=' . urlencode($this->purchaseKey);
        }

        return $this->releaseUrl;
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
