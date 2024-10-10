<?php

namespace AnisAronno\LaravelAutoUpdater\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Class ComposerService
 *
 * Service for running composer commands.
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
        $this->executeComposerCommand('install --no-interaction');
    }

    /**
     * Run composer update.
     *
     * @throws Exception
     */
    public function runComposerUpdate()
    {
        $this->executeComposerCommand('update --no-interaction');
    }

    /**
     * Execute the composer command.
     *
     * @throws Exception
     */
    protected function executeComposerCommand(string $command)
    {
        try {
            $result = Process::run("composer $command 2>&1");
            if (! $result->successful()) {
                $this->handleCommandFailure($result->output());
            }
            Log::info("Composer command executed successfully: $command");
            Log::info('Output: '.$result->output());
        } catch (\Throwable $e) {
            $this->handleCommandFailure($e->getMessage());
        }
    }

    /**
     * Handle a failed composer command.
     *
     * @param string $output
     *
     * @throws Exception
     */
    protected function handleCommandFailure(string $output)
    {
        Log::error("Composer command failed. Error output: $output");

        if (str_contains($output, 'Failed to open stream')) {
            throw new Exception('Composer command failed due to missing files. '.$output);
        }

        throw new Exception('Composer command failed: '.$output);
    }
}
