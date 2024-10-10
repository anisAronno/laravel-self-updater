<?php

namespace AnisAronno\LaravelAutoUpdater\Services;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Class DownloadService
 *
 * Service for downloading files.
 */
class DownloadService
{
    /**
     * Download a file from the given URL.
     *
     * @param string $url
     * @param string $destination
     * @param Command $command
     *
     * @throws Exception
     */
    public function download(string $url, string $destination, Command $command)
    {
        $command->info("Downloading update from: $url");

        try {
            $requestTimeout = config('auto-updater.request_timeout', 120);

            $response = Http::timeout($requestTimeout)->get($url);

            if ($response->failed()) {
                throw new Exception("Failed to download update: HTTP status {$response->status()}");
            }

            $this->saveFile($destination, $response->body());

            $command->info("Download completed: $destination");
            Log::info("Update downloaded successfully to: $destination");
        } catch (Exception $e) {
            Log::error('Download failed: '.$e->getMessage());
            $command->error('Download failed: '.$e->getMessage());

            throw $e;
        }
    }

    /**
     * Save the file to the given destination.
     *
     * @param string $destination
     * @param string $content
     */
    protected function saveFile(string $destination, string $content)
    {
        $directory = dirname($destination);
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($destination, $content);
    }
}
