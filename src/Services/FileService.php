<?php

namespace AnisAronno\LaravelAutoUpdater\Services;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

/**
 * Class FileService
 * 
 * This class provides various file-related operations.
 */
class FileService
{
    /**
     * Get the list of files to back up.
     * 
     * @param string $basePath
     * @return array
     */
    public function getFilesToBackup(string $basePath): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $filesToBackup = [];

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                continue;
            }

            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $item->getPathname());

            if ($this->shouldExclude($relativePath)) {
                continue;
            }

            $targetPath = $basePath . DIRECTORY_SEPARATOR . $relativePath;
            $filesToBackup[$item->getPathname()] = $targetPath;
        }

        return $filesToBackup;
    }

    /**
     * Extract a zip file.
     * 
     * @param string $filePath
     * @param string $extractTo
     * @param Command $command
     * @return string
     * @throws \Exception
     */
    public function extractZip(string $filePath, string $extractTo, Command $command): string
    {
        $zip = new ZipArchive();

        if ($zip->open($filePath) === true) {
            File::ensureDirectoryExists($extractTo);
            $zip->extractTo($extractTo);
            $zip->close();

            $extractedDirs = glob($extractTo.'/*', GLOB_ONLYDIR);

            if (empty($extractedDirs)) {
                throw new \Exception('Failed to locate extracted directory.');
            }

            $command->info('Zip file extracted successfully.');
            return $extractedDirs[0];
        } else {
            throw new \Exception('Failed to open the zip file.');
        }
    }

    /**
     * Replace project files with the new files.
     * 
     * @param string $source
     * @param string $destination
     * @param Command $command
     */
    public function replaceProjectFiles(string $source, string $destination, Command $command)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $command->info('Replacing project files...');
        $progressBar = $command->getOutput()->createProgressBar(iterator_count($iterator));
        $progressBar->start();

        foreach ($iterator as $item) {
            $target = str_replace($source, $destination, $item->getPathname());

            if ($this->shouldSkipFile($target, $destination)) {
                continue;
            }

            if ($item->isDir()) {
                File::ensureDirectoryExists($target);
            } else {
                File::copy($item->getPathname(), $target, true);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $command->info("\nProject files replaced successfully.");
    }

    /**
     * Remove old files from the destination directory.
     * 
     * @param string $source
     * @param string $destination
     * @param Command $command
     */
    public function removeOldFiles(string $source, string $destination, Command $command)
    {
        $sourceFiles = $this->getFileList($source);
        $destFiles = $this->getFileList($destination);

        $filesToRemove = array_diff($destFiles, $sourceFiles);

        $command->info('Removing old files...');
        $progressBar = $command->getOutput()->createProgressBar(count($filesToRemove));
        $progressBar->start();

        foreach ($filesToRemove as $file) {
            $fullPath = $destination . DIRECTORY_SEPARATOR . $file;

            if ($this->shouldSkipFile($fullPath, $destination)) {
                continue;
            }

            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $command->info("\nOld files removed successfully.");

        $this->removeEmptyDirectories($destination, $command);
    }

    /**
     * Delete a file or directory.
     * 
     * @param string $path
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
     * Check if a file should be excluded from the backup.
     * 
     * @param string $path
     * @return bool
     */
    protected function shouldExclude(string $path): bool
    {
        return $this->shouldSkipFile($path, base_path());
    }

    /**
     * Check if a file should be skipped.
     * 
     * @param string $path
     * @param string $basePath
     * @return bool
     */
    protected function shouldSkipFile(string $path, string $basePath): bool
    {
        $excludeItems = config('auto-updater.exclude_items', []);
        
        $skipPaths = array_merge([
            storage_path(),
            $basePath . DIRECTORY_SEPARATOR . '.env',
            $basePath . DIRECTORY_SEPARATOR . '.git',
            $basePath . DIRECTORY_SEPARATOR . 'vendor',
            $basePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'database.sqlite'
        ], $excludeItems);

        // Check if the path starts with any of the skip paths
        foreach ($skipPaths as $skipPath) {
            if (strpos($path, $skipPath) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the list of files in a directory.
     * 
     * @param string $dir
     * @return array
     */
    protected function getFileList(string $dir): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if (!$file->isDir()) {
                $files[] = str_replace($dir . DIRECTORY_SEPARATOR, '', $file->getPathname());
            }
        }

        return $files;
    }

    /**
     * Remove empty directories from a directory.
     * 
     * @param string $dir
     * @param Command $command
     */
    protected function removeEmptyDirectories(string $dir, Command $command)
    {
        $command->info('Removing empty directories...');
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $path) {
            if ($path->isDir()) {
                $dirPath = $path->getPathname();
                if (!(new \RecursiveDirectoryIterator($dirPath))->valid()) {
                    rmdir($dirPath);
                    $command->line("Removed empty directory: $dirPath");
                }
            }
        }
        $command->info('Empty directories removed.');
    }

    /**
     * Cleanup the temporary files.
     * 
     * @param array $paths
     * @param Command $command
     */
    public function cleanup(array $paths, Command $command)
    {
        foreach ($paths as $path) {
            $this->delete($path);
        }
        $command->info('Cleanup completed.');
    }

}
