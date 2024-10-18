<?php

namespace AnisAronno\LaravelSelfUpdater\Tests;

use AnisAronno\LaravelSelfUpdater\Services\FileService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Mockery;
use ZipArchive;

class FileServiceTest extends TestCase
{
    protected $fileService;

    protected $tempDir;

    protected $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fileService = new FileService();

        // Create a temporary directory for all file operations
        $this->tempDir = $this->app->basePath('temp/file_service_test_'.time());
        File::makeDirectory($this->tempDir, 0755, true, true);

        // Mock the Command class
        $this->command = Mockery::mock(Command::class);
        $this->command->shouldReceive('info')->byDefault();
        $this->command->shouldReceive('warn')->byDefault();
        $this->command->shouldReceive('line')->byDefault();

        // Create a stub for ProgressBar
        $progressBar = new class () {
            public function start()
            {
            }

            public function advance()
            {
            }

            public function finish()
            {
            }
        };

        // Mock the output and progress bar creation
        $output = Mockery::mock('Symfony\Component\Console\Output\OutputInterface');
        $output->shouldReceive('createProgressBar')->andReturn($progressBar);
        $this->command->shouldReceive('getOutput')->andReturn($output);
    }

    public function testGetFilesToBackup()
    {
        // Create test files
        File::put($this->tempDir.'/file1.txt', 'Content 1');
        File::put($this->tempDir.'/file2.txt', 'Content 2');
        File::makeDirectory($this->tempDir.'/subdir');
        File::put($this->tempDir.'/subdir/file3.txt', 'Content 3');

        $filesToBackup = $this->fileService->getFilesToBackup($this->tempDir);

        $this->assertCount(3, $filesToBackup);

        $expectedPaths = [
            $this->tempDir.'/file1.txt' => 'file1.txt',
            $this->tempDir.'/file2.txt' => 'file2.txt',
            $this->tempDir.'/subdir/file3.txt' => 'subdir'.DIRECTORY_SEPARATOR.'file3.txt',
        ];

        foreach ($expectedPaths as $fullPath => $relativePath) {
            $this->assertArrayHasKey($fullPath, $filesToBackup);
            $this->assertEquals($relativePath, $filesToBackup[$fullPath]);
        }
    }

    public function testExtractZip()
    {
        $zipFile = $this->tempDir.'/test.zip';
        $extractTo = $this->tempDir.'/extracted';

        // Create a test zip file
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE) === true) {
            $zip->addFromString('test.txt', 'Test content');
            $zip->addEmptyDir('empty_dir');
            $zip->close();
        } else {
            $this->fail('Failed to create test zip file');
        }

        $this->command->shouldReceive('info')->once()->with('Zip file extracted successfully.');

        $result = $this->fileService->extractZip($zipFile, $extractTo, $this->command);

        $this->assertDirectoryExists($extractTo);
        $this->assertFileExists($extractTo.'/test.txt');
        $this->assertDirectoryExists($extractTo.'/empty_dir');
    }

    public function testReplaceProjectFiles()
    {
        $source = $this->tempDir.'/source';
        $destination = $this->tempDir.'/destination';

        // Create source files
        File::makeDirectory($source);
        File::put($source.'/file1.txt', 'Source Content 1');
        File::put($source.'/file2.txt', 'Source Content 2');

        // Create destination files
        File::makeDirectory($destination);
        File::put($destination.'/file1.txt', 'Destination Content 1');
        File::put($destination.'/file3.txt', 'Destination Content 3');

        $this->command->shouldReceive('info')->with('Replacing project files...');
        $this->command->shouldReceive('info')->with("\nProject files replaced successfully.");

        $this->fileService->replaceProjectFiles($source, $destination, $this->command);

        $this->assertFileExists($destination.'/file1.txt');
        $this->assertFileExists($destination.'/file2.txt');
        $this->assertFileExists($destination.'/file3.txt');
        $this->assertEquals('Source Content 1', File::get($destination.'/file1.txt'));
        $this->assertEquals('Source Content 2', File::get($destination.'/file2.txt'));
    }

    public function testRemoveOldFiles()
    {
        $source = $this->tempDir.'/source';
        $destination = $this->tempDir.'/destination';

        // Create source files
        File::makeDirectory($source);
        File::put($source.'/file1.txt', 'Content 1');

        // Create destination files
        File::makeDirectory($destination);
        File::put($destination.'/file1.txt', 'Content 1');
        File::put($destination.'/file2.txt', 'Content 2');

        $this->command->shouldReceive('info')->with('Removing old files...');
        $this->command->shouldReceive('info')->with("\nOld files removed successfully.");
        $this->command->shouldReceive('info')->with('Removing empty directories...');
        $this->command->shouldReceive('info')->with('Empty directories removal process completed.');

        $this->fileService->removeOldFiles($source, $destination, $this->command);

        $this->assertFileExists($destination.'/file1.txt');
        $this->assertFileDoesNotExist($destination.'/file2.txt');
    }

    public function testRemoveEmptyDirectories()
    {
        $dir = $this->tempDir.'/test_dir';
        File::makeDirectory($dir.'/empty_dir', 0755, true);
        File::makeDirectory($dir.'/non_empty_dir', 0755, true);
        File::put($dir.'/non_empty_dir/file.txt', 'Content');

        $this->command->shouldReceive('info')->with('Removing old files...');
        $this->command->shouldReceive('info')->with("\nOld files removed successfully.");
        $this->command->shouldReceive('info')->with('Removing empty directories...');
        $this->command->shouldReceive('info')->with('Empty directories removal process completed.');

        $this->fileService->removeOldFiles($dir, $dir, $this->command);

        $this->assertDirectoryDoesNotExist($dir.'/empty_dir');
        $this->assertDirectoryExists($dir.'/non_empty_dir');
    }

    public function testDelete()
    {
        $file = $this->tempDir.'/test_delete.txt';
        File::put($file, 'Test content');

        $this->fileService->delete($file);

        $this->assertFileDoesNotExist($file);
    }

    public function testCleanup()
    {
        $paths = [
            $this->tempDir.'/file1.txt',
            $this->tempDir.'/dir1',
        ];

        File::put($paths[0], 'Content');
        File::makeDirectory($paths[1]);

        $this->command->shouldReceive('info')->with('Cleanup completed.');

        $this->fileService->cleanup($paths, $this->command);

        foreach ($paths as $path) {
            $this->assertFileDoesNotExist($path);
        }
    }

    protected function tearDown(): void
    {
        // Clean up the temporary directory
        if (File::isDirectory($this->tempDir)) {
            File::deleteDirectory($this->tempDir);
        }

        Mockery::close();
        parent::tearDown();
    }
}
