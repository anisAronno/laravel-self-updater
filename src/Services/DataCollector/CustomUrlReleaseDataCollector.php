<?php

namespace AnisAronno\LaravelAutoUpdater\Services\DataCollector;

use AnisAronno\LaravelAutoUpdater\Contracts\ReleaseDataCollectorInterface;
use Illuminate\Support\Facades\Http;

/**
 * Class CustomUrlReleaseDataCollector
 *
 * Custom URL release data collector.
 */
class CustomUrlReleaseDataCollector implements ReleaseDataCollectorInterface
{
    /**
     * Collect the release data for the given version.
     *
     * @param string|null $version
     * @return array
     */
    public function collectReleaseData(?string $version): array
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
