<?php

namespace AnisAronno\LaravelAutoUpdater\Services;

use Exception;
use Illuminate\Support\Facades\Process;

/**
 * Class ComposerService
 * 
 * Service class to run composer install.
 */
class ComposerService
{
    /**
     * Run composer install.
     *
     * @throws Exception
     */
    public function runComposerInstall()
    {
        $composerPath = $this->getComposerPath();
        $this->executeComposerInstall($composerPath);
    }

    /**
     * Get the path to the composer executable.
     *
     * @return string
     * @throws Exception
     */
    protected function getComposerPath(): string
    {
        $result = Process::run('which composer');

        if (!$result->successful()) {
            throw new Exception('Composer is not installed or not found in the system PATH.');
        }

        return trim($result->output());
    }

    /**
     * Execute the composer install command.
     *
     * @param string $composerPath
     * @return bool
     * @throws Exception
     */
    protected function executeComposerInstall(string $composerPath): bool
    {
        try {
            $result = Process::run("$composerPath install --no-interaction 2>&1");

            if (!$result->successful()) {
                $this->handleInstallFailure($result->output());
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            $this->handleInstallFailure($e->getMessage());
            return false;
        }
    }

    /**
     * Handle a failed composer install command.
     *
     * @param string $output
     * @throws Exception
     */
    protected function handleInstallFailure(string $output)
    {
        // Check for specific errors in the output
        if (str_contains($output, 'Failed to open stream')) {
            throw new Exception('Composer install failed due to missing files. ' . $output);
        }

        throw new Exception('Composer install failed: ' . $output);
    }
}