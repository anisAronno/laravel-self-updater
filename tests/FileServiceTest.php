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
        $this->tempDir = $this->app->basePath('temp/file_service_test_' . time());
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
        $testDir = $this->tempDir . '/backup_test';
        File::makeDirectory($testDir . '/subdir', 0755, true, true);
        File::put($testDir . '/file1.txt', 'content');
        File::put($testDir . '/subdir/file2.txt', 'content');

        $result = $this->fileService->getFilesToBackup($testDir);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey($testDir . '/file1.txt', $result);
        $this->assertArrayHasKey($testDir . '/subdir/file2.txt', $result);
    }

    public function testExtractZip()
    {
        $zipFile = $this->tempDir . '/test.zip';
        $extractTo = $this->tempDir . '/extracted';

        // Create a test zip file
        $zip = new ZipArchive;
        if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
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
        $this->assertFileExists($extractTo . '/test.txt');
        $this->assertDirectoryExists($extractTo . '/empty_dir');
    }

    public function testDelete()
    {
        $file = $this->tempDir . '/test_delete.txt';
        File::put($file, 'Test content');

        $this->fileService->delete($file);

        $this->assertFileDoesNotExist($file);
    }
}