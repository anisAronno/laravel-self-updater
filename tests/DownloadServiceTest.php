<?php

namespace AnisAronno\LaravelAutoUpdater\Tests;

use AnisAronno\LaravelAutoUpdater\Services\DownloadService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;

class DownloadServiceTest extends TestCase
{
    protected $downloadService;

    protected $command;

    protected $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->downloadService = new DownloadService();
        $this->command = Mockery::mock(Command::class);

        // Create a temporary directory for downloads
        $this->tempDir = $this->app->basePath('temp/download_service_test_'.time());
        File::makeDirectory($this->tempDir, 0755, true, true);

        // Mock Log facade for all tests
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();
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

    public function testSuccessfulDownload()
    {
        $url = 'https://example.com/update.zip';
        $destination = $this->tempDir.'/update.zip';
        $content = 'Mock file content';

        $responseMock = Mockery::mock('Illuminate\Http\Client\Response');
        $responseMock->shouldReceive('failed')->andReturn(false);
        $responseMock->shouldReceive('body')->andReturn($content);

        Http::shouldReceive('timeout')->andReturnSelf();
        Http::shouldReceive('get')->with($url)->andReturn($responseMock);

        $this->command->shouldReceive('info')->twice();

        $this->downloadService->download($url, $destination, $this->command);

        $this->assertFileExists($destination);
        $this->assertEquals($content, File::get($destination));
    }

    public function testFailedDownload()
    {
        $url = 'https://example.com/update.zip';
        $destination = $this->tempDir.'/update.zip';

        $responseMock = Mockery::mock('Illuminate\Http\Client\Response');
        $responseMock->shouldReceive('failed')->andReturn(true);
        $responseMock->shouldReceive('status')->andReturn(404);

        Http::shouldReceive('timeout')->andReturnSelf();
        Http::shouldReceive('get')->with($url)->andReturn($responseMock);

        $this->command->shouldReceive('info')->once();
        $this->command->shouldReceive('error')->once();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to download update: HTTP status 404');

        $this->downloadService->download($url, $destination, $this->command);
    }

    public function testDownloadWithInvalidDestination()
    {
        $url = 'https://example.com/update.zip';
        $destination = '/invalid/path/update.zip';
        $content = 'Mock file content';

        $responseMock = Mockery::mock('Illuminate\Http\Client\Response');
        $responseMock->shouldReceive('failed')->andReturn(false);
        $responseMock->shouldReceive('body')->andReturn($content);

        Http::shouldReceive('timeout')->andReturnSelf();
        Http::shouldReceive('get')->with($url)->andReturn($responseMock);

        $this->command->shouldReceive('info')->once();
        $this->command->shouldReceive('error')->once();

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/(?:mkdir|file_put_contents|Failed to create directory|Failed to save file)\(\):/');

        $this->downloadService->download($url, $destination, $this->command);

        $this->assertFileDoesNotExist($destination);
    }
}
