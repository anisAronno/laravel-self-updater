<?php

namespace AnisAronno\LaravelAutoUpdater\Services;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Class UpdateOrchestrator
 *
 * This class is responsible for orchestrating the update process.
 * It coordinates the execution of various tasks required to update the project.
 */
class UpdateOrchestrator
{
    protected BackupService $backupService;

    protected DownloadService $downloadService;

    protected FileService $fileService;

    protected ComposerService $composerService;

    /**
     * UpdateOrchestrator constructor.
     */
    public function __construct(BackupService $backupService, DownloadService $downloadService, FileService $fileService, ComposerService $composerService)
    {
        $this->backupService = $backupService;
        $this->downloadService = $downloadService;
        $this->fileService = $fileService;
        $this->composerService = $composerService;
    }

    /**
     * Process the update.
     *
     * @throws Exception
     */
    public function processUpdate(array $releaseData, Command $command)
    {
        $backupPath = null;

        try {
            $this->enableMaintenanceMode($command);
            $backupPath = $this->createBackup($command);
            $this->updateProject($releaseData, $command);
            $this->runMigrations($command);
            $this->clearCache($command);
            $this->installComposerDependencies($command);
            $this->cleanup([$backupPath], $command);

            Log::info('Update completed successfully.');
        } catch (Exception $e) {
            $this->handleFailure($e, $backupPath, $command);
        } finally {
            $this->disableMaintenanceMode($command);
        }
    }

    /**
     * Enable maintenance mode.
     */
    protected function enableMaintenanceMode(Command $command)
    {
        Artisan::call('down');
        $command->info('Maintenance mode enabled.');
    }

    /**
     * Disable maintenance mode.
     */
    protected function disableMaintenanceMode(Command $command)
    {
        Artisan::call('up');
        $command->info('Maintenance mode disabled.');
    }

    /**
     * Create a backup of the project files.
     */
    protected function createBackup(Command $command): string
    {
        $backupPath = $this->backupService->backup($command);
        $command->info('Backup completed successfully.');

        return $backupPath;
    }

    /**
     * Update the project files.
     *
     * @throws Exception
     */
    protected function updateProject(array $releaseData, Command $command)
    {
        $zipballUrl = $this->getUpdateUrl($releaseData);
        $tempFile = storage_path('app/update.zip');
        $tempDir = storage_path('app/update_temp');

        $this->downloadService->download($zipballUrl, $tempFile, $command);
        $extractedDir = $this->fileService->extractZip($tempFile, $tempDir, $command);
        $this->fileService->replaceProjectFiles($extractedDir, base_path(), $command);
        $this->fileService->removeOldFiles($extractedDir, base_path(), $command);
        $this->cleanup([$tempFile, $tempDir], $command);
        $command->info('Files updated successfully.');
    }

    /**
     * Get the update URL from the release data.
     *
     * @throws Exception
     */
    protected function getUpdateUrl(array $releaseData): string
    {
        $zipballUrl = $releaseData['download_url'] ?? null;
        if (is_null($zipballUrl)) {
            throw new Exception('No update available.');
        }

        return $zipballUrl;
    }

    /**
     * Run the migrations.
     */
    protected function runMigrations(Command $command)
    {
        $command->info('Running migrations...');
        Artisan::call('migrate', ['--force' => true]);
        $command->info('Migrations completed.');
    }

    /**
     * Clear the cache.
     */
    protected function clearCache(Command $command)
    {
        $command->info('Clearing cache...');
        Artisan::call('optimize:clear');
        Process::run('composer clear-cache');
        $command->info('Cache cleared.');
    }

    /**
     * Install the Composer dependencies.
     *
     * @throws Exception
     */
    protected function installComposerDependencies(Command $command)
    {
        $command->info('Running composer install...');
        $this->composerService->runComposerInstall(); // Use ComposerService
        $command->info('Composer install completed.');
    }

    /**
     * Cleanup the temporary files.
     */
    protected function cleanup(array $paths, Command $command)
    {
        $command->info('Cleaning up...');
        $this->fileService->cleanup($paths, $command);
        $command->info('Cleanup completed.');
    }

    /**
     * Handle the failure scenario.
     *
     * @throws Exception
     */
    protected function handleFailure(Exception $e, string $backupPath, Command $command)
    {
        Log::error("Update failed: {$e->getMessage()}");
        $command->error("Update failed: {$e->getMessage()}");

        if ($backupPath) {
            $command->info('Rolling back to previous version...');
            $this->backupService->rollback($backupPath, $command);
            $command->info('Rollback completed.');
        }

        throw $e;
    }
}
