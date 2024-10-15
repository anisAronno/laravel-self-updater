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
        ($this->artisanCaller)('down');
        $command->info('Maintenance mode enabled.');
    }

    /**
     * Disable maintenance mode.
     */
    protected function disableMaintenanceMode(Command $command)
    {
        ($this->artisanCaller)('up');
        $command->info('Maintenance mode disabled.');
    }

    /**
     * Create a backup.
     */
    protected function createBackup(Command $command): string
    {
        $backupPath = $this->backupService->backup($command);
        $command->info('Backup completed successfully.');

        return $backupPath;
    }

    /**
     * Update the project.
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
     */
    protected function runMigrations(Command $command)
    {
        $command->info('Running migrations...');
        ($this->artisanCaller)('migrate', ['--force' => true]);
        $command->info('Migrations completed.');
    }

    /**
     * Clear cache.
     */
    protected function clearCache(Command $command)
    {
        $command->info('Clearing cache...');
        ($this->artisanCaller)('optimize:clear');
        $command->info('Cache cleared.');
    }

    /**
     * Install composer dependencies.
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
