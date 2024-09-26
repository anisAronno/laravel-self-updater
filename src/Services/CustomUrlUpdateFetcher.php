<?php

namespace AnisAronno\LaravelAutoUpdater\Services;

use AnisAronno\LaravelAutoUpdater\Contracts\UpdateFetcherInterface;
use Illuminate\Support\Facades\Http;

/**
 * Class CustomUrlUpdateFetcher
 * 
 * This class fetches the release data from a custom URL.
 */
class CustomUrlUpdateFetcher implements UpdateFetcherInterface
{
    /**
     * Fetch the release data from the custom URL.
     *
     * @param string|null $version
     * @return array
     */
    public function fetchReleaseData(?string $version): array
    {
        $release_url = config('auto-updater-config.custom_url');
        $response  = Http::withHeaders(['User-Agent' => 'PHP'])->get($release_url);

        if ($response->failed()) {
            return []; // Handle the error as per your needs (e.g., log the error)
        }

        $data = $response->json();

        return array_merge($data, [
            'version'      => $data['version'] ? ltrim($data['version'], 'v') : null,
            'download_url' => $data['download_url'] ?? null,
        ]);
    }
}
