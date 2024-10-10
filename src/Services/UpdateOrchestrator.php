<?php

namespace AnisAronno\LaravelAutoUpdater\Services;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Class UpdateOrchestrator
 *
 * Service for orchestrating the update process.
 */
class UpdateOrchestrator
{
    protected BackupService $backupService;

    protected DownloadService $downloadService;

    protected FileService $fileService;

    protected ComposerService $composerService;

    protected $artisanCaller;

    /**
     * UpdateOrchestrator constructor.
     *
     * @param BackupService $backupService
     * @param DownloadService $downloadService
     * @param FileService $fileService
     * @param ComposerService $composerService
     * @param callable|null $artisanCaller
     */
    public function __construct(
        BackupService $backupService,
        DownloadService $downloadService,
        FileService $fileService,
        ComposerService $composerService,
        ?callable $artisanCaller = null
    ) {
        $this->backupService = $backupService;
        $this->downloadService = $downloadService;
        $this->fileService = $fileService;
        $this->composerService = $composerService;
        $this->artisanCaller = $artisanCaller ?? function ($command, $parameters = []) {
            return Artisan::call($command, $parameters);
        };
    }

    /**
     * Process the update.
     *
     * @param array $releaseData
     * @param Command $command
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
     *
     * @param Command $command
     */
    protected function enableMaintenanceMode(Command $command)
    {
        ($this->artisanCaller)('down');
        $command->info('Maintenance mode enabled.');
    }

    /**
     * Disable maintenance mode.
     *
     * @param Command $command
     */
    protected function disableMaintenanceMode(Command $command)
    {
        ($this->artisanCaller)('up');
        $command->info('Maintenance mode disabled.');
    }

    /**
     * Create a backup.
     *
     * @param Command $command
     *
     * @return string
     */
    protected function createBackup(Command $command): string
    {
        $backupPath = $this->backupService->backup($command);
        $command->info('Backup completed successfully.');

        return $backupPath;
    }

    /**
     * Update the project.
     *
     * @param array $releaseData
     * @param Command $command
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
     * Run migrations.
     *
     * @param Command $command
     */
    protected function runMigrations(Command $command)
    {
        $command->info('Running migrations...');
        ($this->artisanCaller)('migrate', ['--force' => true]);
        $command->info('Migrations completed.');
    }

    /**
     * Clear cache.
     *
     * @param Command $command
     */
    protected function clearCache(Command $command)
    {
        $command->info('Clearing cache...');
        ($this->artisanCaller)('optimize:clear');
        $command->info('Cache cleared.');
    }

    /**
     * Install composer dependencies.
     *
     * @param Command $command
     */
    protected function installComposerDependencies(Command $command)
    {
        $requireComposerInstall = config('auto-updater.require_composer_install', false);
        $requireComposerUpdate = config('auto-updater.require_composer_update', false);

        if (! $requireComposerInstall && ! $requireComposerUpdate) {
            $command->info('Skipping composer install/update.');

            return;
        }

        if ($requireComposerInstall) {
            $command->info('Running composer install...');
            $this->composerService->runComposerInstall();
            $command->info('Composer install completed.');
        }

        if ($requireComposerUpdate) {
            $command->info('Running composer update...');
            $this->composerService->runComposerUpdate();
            $command->info('Composer update completed.');
        }
    }

    /**
     * Cleanup.
     *
     * @param array $paths
     * @param Command $command
     */
    protected function cleanup(array $paths, Command $command)
    {
        $command->info('Cleaning up...');
        $this->fileService->cleanup($paths, $command);
        $command->info('Cleanup completed.');
    }

    /**
     * Handle a failed update.
     *
     * @param Exception $e
     * @param string|null $backupPath
     * @param Command $command
     *
     * @throws Exception
     */
    protected function handleFailure(Exception $e, ?string $backupPath, Command $command)
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

    /**
     * Get the update URL.
     *
     * @param array $releaseData
     *
     * @return string
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
}
