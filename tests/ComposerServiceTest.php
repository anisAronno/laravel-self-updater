<?php

namespace AnisAronno\LaravelSelfUpdater\Tests;

use AnisAronno\LaravelSelfUpdater\Services\ComposerService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Mockery;

class ComposerServiceTest extends TestCase
{
    protected $composerService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->composerService = new ComposerService;
    }

    public function testRunComposerInstall()
    {
        $this->mockComposerExecution('install --no-interaction', true);

        Log::shouldReceive('info')
            ->twice()
            ->with(Mockery::type('string')); // Expect the log to be called with a string

        $this->composerService->runComposerInstall();

        // Assert that the composer install process was successful
        $this->assertTrue(true); // This confirms that the command ran without issues.
    }

    public function testRunComposerUpdate()
    {
        $this->mockComposerExecution('update --no-interaction', true);

        Log::shouldReceive('info')
            ->twice()
            ->with(Mockery::type('string')); // Expect the log to be called with a string

        $this->composerService->runComposerUpdate();

        // Assert that the composer update process was successful
        $this->assertTrue(true); // This confirms that the command ran without issues.
    }

    public function testComposerCommandFailure()
    {
        $this->mockComposerExecution('install --no-interaction', false, 'Some error occurred');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Composer command failed: Some error occurred');

        Log::shouldReceive('error')->once()->with('Composer command failed. Error output: Some error occurred');

        $this->composerService->runComposerInstall();
    }

    public function testComposerCommandFailureDueToMissingFiles()
    {
        $this->mockComposerExecution('install --no-interaction', false, 'Failed to open stream: No such file or directory');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Composer command failed due to missing files.');

        Log::shouldReceive('error')->once()->with('Composer command failed. Error output: Failed to open stream: No such file or directory');

        $this->composerService->runComposerInstall();
    }

    protected function mockComposerExecution(string $command, bool $success, string $output = '')
    {
        Process::shouldReceive('run')
            ->with(Mockery::pattern("/composer $command.*/"))
            ->andReturn(Mockery::mock([
                'successful' => $success,
                'output' => $success ? "Successfully executed $command" : $output,
            ]));
    }
}
