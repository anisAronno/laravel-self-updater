<?php

namespace AnisAronno\LaravelAutoUpdater\Services;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class BackupService
{
    protected FileService $fileService;

    /**
     * BackupService constructor.
     */
    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    /**
     * Create a backup of the project files.
     *
     * @throws Exception
     */
    public function backup(Command $command): string
    {
        $backupPath = $this->getBackupPath();

        try {
            File::ensureDirectoryExists($backupPath);

            $command->info('Starting backup process...');
            $filesToBackup = $this->fileService->getFilesToBackup(base_path());

            $zip = new ZipArchive;
            $zipPath = $backupPath.'/backup.zip';

            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception("Cannot create zip file: $zipPath");
            }

            $progressBar = $command->getOutput()->createProgressBar(count($filesToBackup));
            $progressBar->start();

            foreach ($filesToBackup as $source => $target) {
                $relativeTarget = str_replace(base_path().'/', '', $target);
                if (File::isDirectory($source)) {
                    $zip->addEmptyDir($relativeTarget);
                } else {
                    $zip->addFile($source, $relativeTarget);
                }
                $progressBar->advance();
            }

            $zip->close();
            $progressBar->finish();

            $command->info("\nBackup completed: $zipPath");

            $this->logBackupDetails($backupPath, $zipPath);

            return $backupPath;
        } catch (Exception $e) {
            Log::error('Backup failed: '.$e->getMessage());
            $command->error('Backup failed: '.$e->getMessage());

            throw $e;
        }
    }

    /**
     * Rollback to the backup.
     *
     * @throws Exception
     */
    public function rollback(string $backupPath, Command $command)
    {
        $zipPath = $backupPath.'/backup.zip';

        if (! File::exists($zipPath)) {
            throw new Exception("Backup not found: $zipPath");
        }

        $command->info('Rolling back to backup...');

        try {
            $zip = new ZipArchive;
            if ($zip->open($zipPath) !== true) {
                throw new Exception("Cannot open zip file: $zipPath");
            }

            $extractPath = storage_path('app/rollback_temp');
            File::ensureDirectoryExists($extractPath);

            $zip->extractTo($extractPath);
            $zip->close();

            $this->fileService->replaceProjectFiles($extractPath, base_path(), $command);

            File::deleteDirectory($extractPath);

            $command->info("Rolled back to backup: $backupPath");
        } catch (Exception $e) {
            Log::error('Rollback failed: '.$e->getMessage());
            $command->error('Rollback failed: '.$e->getMessage());

            throw $e;
        }
    }

    /**
     * Get the backup path.
     */
    protected function getBackupPath(): string
    {
        return storage_path('app/backup/'.date('Y-m-d_H-i-s'));
    }

    /**
     * Log backup details.
     */
    protected function logBackupDetails(string $backupPath, string $zipPath): void
    {
        $backupSize = File::size($zipPath);
        $backupFiles = count(File::allFiles($backupPath));

        Log::info("Backup created at: $backupPath");
        Log::info('Backup size: '.$this->formatBytes($backupSize));
        Log::info("Files backed up: $backupFiles");
    }

    /**
     * Format bytes to human-readable format.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }
}
