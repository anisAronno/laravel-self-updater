<?php

namespace AnisAronno\LaravelAutoUpdater\Services;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Class BackupService
 * 
 * This class provides backup-related operations.
 */
class BackupService
{
    protected FileService $fileService;

    /**
     * BackupService constructor.
     * 
     * @param FileService $fileService
     */
    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    /**
     * Create a backup of the project files.
     * 
     * @param Command $command
     * @return string
     */
    public function backup(Command $command)
    {
        $backupPath = storage_path('app/backup/' . date('Y-m-d_H-i-s'));
        File::ensureDirectoryExists($backupPath);

        $command->info('Starting backup process...');
        $filesToBackup = $this->fileService->getFilesToBackup(base_path());

        $progressBar = $command->getOutput()->createProgressBar(count($filesToBackup));
        $progressBar->start();

        foreach ($filesToBackup as $source => $target) {
            File::ensureDirectoryExists(dirname($target));
            File::copy($source, $target);
            $progressBar->advance();
        }

        $progressBar->finish();
        $command->info("\nBackup completed: $backupPath");

        return $backupPath;
    }

    /**
     * Rollback to the backup.
     * 
     * @param string $backupPath
     * @param Command $command
     */
    public function rollback( string $backupPath, Command $command)
    {
        if (File::isDirectory($backupPath)) {
            $command->info('Rolling back to backup...');
            $this->fileService->replaceProjectFiles($backupPath, base_path(), $command);
            $command->info("Rolled back to backup: $backupPath");
        } else {
            $command->warning("Backup not found: $backupPath");
        }
    }
}
