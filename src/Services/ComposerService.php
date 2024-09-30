<?php

namespace AnisAronno\LaravelAutoUpdater\Services;

use Exception;

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
    protected function getComposerPath(): string {
        $composerPath = exec('which composer');
        
        if (empty($composerPath)) {
            throw new Exception('Composer is not installed or not found in the system PATH.');
        }

        return $composerPath;
    }

    /**
     * Execute the composer install command.
     *
     * @param string $composerPath
     * @return bool
     * @throws Exception
     */
    protected function executeComposerInstall( string $composerPath): bool {
        $output = [];
        $returnVar = 0;

        // Run composer install and capture the output and return code
        exec("$composerPath install --no-interaction 2>&1", $output, $returnVar);

        // Check if the command failed
        if ($returnVar !== 0) {
            $this->handleInstallFailure($output);
        }

        // Composer install was successful
        return true;
    }

    /**
     * Handle a failed composer install command.
     *
     * @param array $output
     * @throws Exception
     */
    protected function handleInstallFailure(array $output)
    {
        $errorMessage = implode("\n", $output);

        // Check for specific errors in the output
        if (strpos($errorMessage, 'Failed to open stream') !== false) {
            throw new Exception('Composer install failed due to missing files. ' . $errorMessage);
        }

        throw new Exception('Composer install failed: ' . $errorMessage);
    }
}
