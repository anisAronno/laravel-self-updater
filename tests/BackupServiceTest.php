<?php

namespace AnisAronno\LaravelSelfUpdater\Tests;

use AnisAronno\LaravelSelfUpdater\Services\BackupService;
use AnisAronno\LaravelSelfUpdater\Services\FileService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Mockery;
use Symfony\Component\Console\Output\OutputInterface;

class BackupServiceTest extends TestCase
{
    protected $backupService;

    protected $fileService;

    protected $command;

    protected $output;

    protected $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileService = new FileService;
        $this->backupService = new BackupService($this->fileService);
        $this->output = Mockery::mock(OutputInterface::class);
        $this->command = Mockery::mock(Command::class);
        $this->command->shouldReceive('getOutput')->andReturn($this->output);

        // Create a temporary directory for all backup operations
        $this->tempDir = $this->app->basePath('temp/backup_service_test_'.time());
        File::makeDirectory($this->tempDir, 0755, true, true);
    }

    public function testBackup()
    {
        // Create test files
        File::put($this->tempDir.'/file1.txt', 'Content 1');
        File::put($this->tempDir.'/file2.txt', 'Content 2');

        // Mock the command and output
        $this->command->shouldReceive('info')->atLeast()->once();
        $this->command->shouldReceive('error')->never();
        $this->output->shouldReceive('createProgressBar')->andReturnSelf();
        $this->output->shouldReceive('start')->once();
        $this->output->shouldReceive('advance')->atLeast()->once();
        $this->output->shouldReceive('finish')->once();

        // Perform backup
        $backupPath = $this->backupService->backup($this->command);

        // Assertions
        $this->assertDirectoryExists($backupPath);
        $this->assertFileExists($backupPath.'/backup.zip');

        // Verify zip contents
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($backupPath.'/backup.zip') === true);
        $this->assertGreaterThan(0, $zip->numFiles);
        $zip->close();
    }

    public function testRollback()
    {
        // Create a mock backup
        $backupPath = $this->tempDir.'/backup';
        File::makeDirectory($backupPath);
        $zipPath = $backupPath.'/backup.zip';

        $zip = new \ZipArchive;
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->addFromString('file1.txt', 'Backup content 1');
        $zip->addFromString('file2.txt', 'Backup content 2');
        $zip->close();

        // Mock the command and output
        $this->command->shouldReceive('info')->atLeast()->once();
        $this->command->shouldReceive('error')->never();
        $this->output->shouldReceive('createProgressBar')->andReturnSelf();
        $this->output->shouldReceive('start')->once();
        $this->output->shouldReceive('advance')->atLeast()->once();
        $this->output->shouldReceive('finish')->once();

        // Perform rollback
        $this->backupService->rollback($backupPath, $this->command);

        // Assertions
        $this->assertFileExists(base_path('file1.txt'));
        $this->assertFileExists(base_path('file2.txt'));
        $this->assertEquals('Backup content 1', File::get(base_path('file1.txt')));
        $this->assertEquals('Backup content 2', File::get(base_path('file2.txt')));

        // Clean up
        File::delete(base_path('file1.txt'));
        File::delete(base_path('file2.txt'));
    }

    public function testRollbackWithNonExistentBackup()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Backup not found: /non/existent/path/backup.zip');

        // We're not expecting the error method to be called anymore
        $this->command->shouldReceive('error')->never();

        $this->backupService->rollback('/non/existent/path', $this->command);
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
