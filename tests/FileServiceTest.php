<?php

namespace AnisAronno\LaravelAutoUpdater\Tests;

use AnisAronno\LaravelAutoUpdater\Services\FileService;
use Illuminate\Support\Facades\File;
use Mockery;
use ZipArchive;

class FileServiceTest extends TestCase
{
    protected $fileService;

    protected $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fileService = new FileService();

        // Create a temporary directory for all file operations
        $this->tempDir = $this->app->basePath('temp/file_service_test_'.time());
        File::makeDirectory($this->tempDir, 0755, true, true);
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

    public function testGetFilesToBackup()
    {
        // Create test files
        File::put($this->tempDir . '/file1.txt', 'Content 1');
        File::put($this->tempDir . '/file2.txt', 'Content 2');
        File::makeDirectory($this->tempDir . '/subdir');
        File::put($this->tempDir . '/subdir/file3.txt', 'Content 3');

        $filesToBackup = $this->fileService->getFilesToBackup($this->tempDir);

        $this->assertCount(3, $filesToBackup);

        $expectedPaths = [
            $this->tempDir . '/file1.txt' => 'file1.txt',
            $this->tempDir . '/file2.txt' => 'file2.txt',
            $this->tempDir . '/subdir/file3.txt' => 'subdir' . DIRECTORY_SEPARATOR . 'file3.txt',
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

        $command = Mockery::mock('Illuminate\Console\Command');
        $command->shouldReceive('info')->once()->with('Zip file extracted successfully.');

        $result = $this->fileService->extractZip($zipFile, $extractTo, $command);

        $this->assertDirectoryExists($extractTo);
        $this->assertFileExists($extractTo.'/test.txt');
        $this->assertDirectoryExists($extractTo.'/empty_dir');
    }

    public function testDelete()
    {
        $file = $this->tempDir.'/test_delete.txt';
        File::put($file, 'Test content');

        $this->fileService->delete($file);

        $this->assertFileDoesNotExist($file);
    }
}
