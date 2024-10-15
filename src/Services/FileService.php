<?php

namespace AnisAronno\LaravelAutoUpdater\Services;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Finder\Finder;
use ZipArchive;

/**
 * Class FileService
 *
 * Service for managing files.
 */
class FileService
{
    protected $excludeItems;

    protected $criticalDirectories;

    public function __construct()
    {
        $this->excludeItems = config('auto-updater.exclude_items', []);
        $this->criticalDirectories = [
            base_path('bootstrap/cache'),
            storage_path('app'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
        ];
    }

    /**
     * Get the list of files to backup.
     */
    public function getFilesToBackup(string $basePath): array
    {
        $finder = new Finder();
        $finder->files()->in($basePath);

        $filesToBackup = [];
        foreach ($finder as $file) {
            $relativePath = $file->getRelativePathname();
            if (! $this->shouldExclude($relativePath)) {
                $filesToBackup[$file->getRealPath()] = $relativePath;
            }
        }

        return $filesToBackup;
    }

    /**
     * Extract a zip file to the given directory.
     *
     * @throws Exception
     */
    public function extractZip(string $filePath, string $extractTo, Command $command): string
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new Exception('Failed to open the zip file.');
        }

        $this->performExtraction($zip, $extractTo);
        $extractedDir = $this->getExtractedDirectory($extractTo);
        $command->info('Zip file extracted successfully.');

        return $extractedDir;
    }

    /**
     * Replace project files with the files from the source directory.
     */
    public function replaceProjectFiles(string $source, string $destination, Command $command)
    {
        $command->info('Replacing project files...');
        $finder = $this->getSourceFinder($source);
        $progressBar = $command->getOutput()->createProgressBar($finder->count());
        $progressBar->start();

        $this->copyFiles($finder, $source, $destination, $progressBar);

        $progressBar->finish();
        $command->info("\nProject files replaced successfully.");
    }

    /**
     * Remove old files from the destination directory.
     */
    public function removeOldFiles(string $source, string $destination, Command $command)
    {
        $command->info('Removing old files...');
        $filesToRemove = $this->getFilesToRemove($source, $destination);
        $progressBar = $command->getOutput()->createProgressBar(count($filesToRemove));
        $progressBar->start();

        $this->deleteOldFiles($filesToRemove, $destination, $progressBar);

        $progressBar->finish();
        $command->info("\nOld files removed successfully.");
        $this->removeEmptyDirectories($destination, $command);
    }

    /**
     * Delete the given path.
     */
    public function delete(string $path)
    {
        if (File::isDirectory($path)) {
            File::deleteDirectory($path);
        } elseif (File::exists($path)) {
            File::delete($path);
        }
    }

    /**
     * Cleanup the given paths.
     */
    public function cleanup(array $paths, Command $command)
    {
        foreach ($paths as $path) {
            try {
                $this->delete($path);
            } catch (Exception $e) {
                $this->logAndNotifyError("Failed to delete {$path}: ".$e->getMessage(), $command);
            }
        }
        $command->info('Cleanup completed.');
    }

    /**
     * Check if a file should be excluded.
     */
    protected function shouldExclude(string $path): bool
    {
        return $this->shouldSkipFile($path, base_path());
    }

    /**
     * Check if a file should be skipped.
     */
    protected function shouldSkipFile(string $path, string $basePath): bool
    {
        $skipPaths = array_merge([
            storage_path(),
            $basePath.DIRECTORY_SEPARATOR.'.env',
            $basePath.DIRECTORY_SEPARATOR.'.git',
            $basePath.DIRECTORY_SEPARATOR.'vendor',
            $basePath.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'database.sqlite',
        ], $this->excludeItems);

        return collect($skipPaths)->contains(fn ($skipPath) => str_starts_with($path, $skipPath));
    }

    /**
     * Get the list of files in the given directory.
     */
    protected function getFileList(string $dir): array
    {
        $finder = new Finder();
        $finder->files()->in($dir);

        return array_map(fn ($file) => $file->getRelativePathname(), iterator_to_array($finder));
    }

    /**
     * Remove empty directories from the given directory.
     */
    protected function removeEmptyDirectories(string $dir, Command $command)
    {
        if (! File::isDirectory($dir)) {
            $command->warn("Directory does not exist: $dir");

            return;
        }

        $command->info('Removing empty directories...');

        $this->processDirectories($dir, $command);

        $command->info('Empty directories removal process completed.');
    }

    /**
     * Process directories in the given directory.
     */
    private function processDirectories(string $dir, Command $command): void
    {
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $path) {
                if ($path->isDir() && ! $path->isLink()) {
                    $this->processDirectory($path->getPathname(), $command);
                }
            }
        } catch (Exception $e) {
            $this->logAndNotifyWarning("Error processing directories in $dir: ".$e->getMessage(), $command);
        }
    }

    /**
     * Process the given directory.
     */
    private function processDirectory(string $dirPath, Command $command): void
    {
        if (! File::isDirectory($dirPath) || in_array($dirPath, $this->criticalDirectories)) {
            return;
        }

        try {
            if ($this->isEmptyDirectory($dirPath)) {
                File::deleteDirectory($dirPath);
                $command->line("Removed empty directory: $dirPath");
            }
        } catch (Exception $e) {
            $this->logAndNotifyWarning("Failed to process directory $dirPath: ".$e->getMessage(), $command);
        }
    }

    /**
     * Check if a directory is empty.
     */
    private function isEmptyDirectory(string $dirPath): bool
    {
        try {
            $iterator = new \FilesystemIterator($dirPath);

            return ! $iterator->valid();
        } catch (Exception $e) {
            // If we can't read the directory, we'll assume it's not empty to be safe
            return false;
        }
    }

    /**
     * Log and notify a warning message.
     */
    private function logAndNotifyWarning(string $message, Command $command): void
    {
        Log::warning($message);
        $command->warn($message);
    }

    /**
     * Perform the extraction of the zip file.
     */
    private function performExtraction(ZipArchive $zip, string $extractTo): void
    {
        File::ensureDirectoryExists($extractTo);
        $zip->extractTo($extractTo);
        $zip->close();
    }

    /**
     * Get the extracted directory.
     *
     * @throws Exception
     */
    private function getExtractedDirectory(string $extractTo): string
    {
        $extractedDirs = File::directories($extractTo);
        if (empty($extractedDirs)) {
            throw new Exception('Failed to locate extracted directory.');
        }

        return $extractedDirs[0];
    }

    /**
     * Get the source finder.
     */
    private function getSourceFinder(string $source): Finder
    {
        $finder = new Finder();

        return $finder->in($source)->ignoreDotFiles(false);
    }

    /**
     * Copy files from the source to the destination.
     */
    private function copyFiles(Finder $finder, string $source, string $destination, $progressBar): void
    {
        foreach ($finder as $item) {
            $target = str_replace($source, $destination, $item->getRealPath());

            if ($this->shouldSkipFile($target, $destination)) {
                continue;
            }

            $this->copyFileOrCreateDirectory($item, $target);
            $progressBar->advance();
        }
    }

    /**
     * Copy a file or create a directory.
     */
    private function copyFileOrCreateDirectory($item, string $target): void
    {
        if ($item->isDir()) {
            File::ensureDirectoryExists($target);
        } else {
            File::copy($item->getRealPath(), $target, true);
        }
    }

    /**
     * Get the list of files to remove.
     */
    private function getFilesToRemove(string $source, string $destination): array
    {
        $sourceFiles = $this->getFileList($source);
        $destFiles = $this->getFileList($destination);

        return array_diff($destFiles, $sourceFiles);
    }

    /**
     * Delete old files from the destination directory.
     */
    private function deleteOldFiles(array $filesToRemove, string $destination, $progressBar): void
    {
        foreach ($filesToRemove as $file) {
            $fullPath = $destination.DIRECTORY_SEPARATOR.$file;

            if ($this->shouldSkipFile($fullPath, $destination)) {
                continue;
            }

            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }

            $progressBar->advance();
        }
    }

    /**
     * Log and notify an error message.
     */
    private function logAndNotifyError(string $message, Command $command): void
    {
        Log::error($message);
        $command->error($message);
    }
}
