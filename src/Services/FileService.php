<?php

namespace AnisAronno\LaravelAutoUpdater\Services;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Finder\Finder;
use ZipArchive;

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
        $finder = new Finder();
        $finder->files()->in($basePath);

        $filesToBackup = [];
        foreach ($finder as $file) {
            $relativePath = $file->getRelativePathname();
            if (! $this->shouldExclude($relativePath)) {
                $filesToBackup[$file->getRealPath()] = $basePath . DIRECTORY_SEPARATOR . $relativePath;
            }
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
     * @throws Exception
     */
    public function extractZip(string $filePath, string $extractTo, Command $command): string
    {
        $zip = new ZipArchive();

        if ($zip->open($filePath) === true) {
            File::ensureDirectoryExists($extractTo);
            $zip->extractTo($extractTo);
            $zip->close();

            $extractedDirs = File::directories($extractTo);

            if (empty($extractedDirs)) {
                throw new Exception('Failed to locate extracted directory.');
            }

            $command->info('Zip file extracted successfully.');

            return $extractedDirs[0];
        } else {
            throw new Exception('Failed to open the zip file.');
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
        $command->info('Replacing project files...');

        $finder = new Finder();
        $finder->in($source)->ignoreDotFiles(false);

        $progressBar = $command->getOutput()->createProgressBar($finder->count());
        $progressBar->start();

        foreach ($finder as $item) {
            $target = str_replace($source, $destination, $item->getRealPath());

            if ($this->shouldSkipFile($target, $destination)) {
                continue;
            }

            if ($item->isDir()) {
                File::ensureDirectoryExists($target);
            } else {
                File::copy($item->getRealPath(), $target, true);
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
        $command->info('Removing old files...');

        $sourceFiles = $this->getFileList($source);
        $destFiles = $this->getFileList($destination);

        $filesToRemove = array_diff($destFiles, $sourceFiles);

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
            $basePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'database.sqlite',
        ], $excludeItems);

        foreach ($skipPaths as $skipPath) {
            if (str_starts_with($path, $skipPath)) {
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
        $finder = new Finder();
        $finder->files()->in($dir);

        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getRelativePathname();
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

        $finder = new Finder();
        $finder->directories()->in($dir);

        foreach ($finder as $directory) {
            if (! (new Finder())->in($directory->getRealPath())->files()->count()) {
                File::deleteDirectory($directory->getRealPath());
                $command->line("Removed empty directory: {$directory->getRealPath()}");
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
            try {
                $this->delete($path);
            } catch (Exception $e) {
                Log::error("Failed to delete {$path}: " . $e->getMessage());
                $command->error("Failed to delete {$path}: " . $e->getMessage());
            }
        }
        $command->info('Cleanup completed.');
    }
}
