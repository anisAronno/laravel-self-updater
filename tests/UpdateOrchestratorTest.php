<?php

namespace AnisAronno\LaravelAutoUpdater\Tests;

use AnisAronno\LaravelAutoUpdater\Services\BackupService;
use AnisAronno\LaravelAutoUpdater\Services\ComposerService;
use AnisAronno\LaravelAutoUpdater\Services\DownloadService;
use AnisAronno\LaravelAutoUpdater\Services\FileService;
use AnisAronno\LaravelAutoUpdater\Services\UpdateOrchestrator;
use Illuminate\Console\Command;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class UpdateOrchestratorTest extends TestCase
{
    protected $backupService;

    protected $downloadService;

    protected $fileService;

    protected $composerService;

    protected $updateOrchestrator;

    protected $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupService = Mockery::mock(BackupService::class);
        $this->downloadService = Mockery::mock(DownloadService::class);
        $this->fileService = Mockery::mock(FileService::class);
        $this->composerService = Mockery::mock(ComposerService::class);
        $this->command = Mockery::mock(Command::class);

        $this->updateOrchestrator = Mockery::mock(UpdateOrchestrator::class, [
            $this->backupService,
            $this->downloadService,
            $this->fileService,
            $this->composerService,
        ])->makePartial()->shouldAllowMockingProtectedMethods();
    }

    public function testProcessUpdateMethodExists()
    {
        $updateOrchestrator = $this->getMockBuilder(UpdateOrchestrator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertTrue(method_exists($updateOrchestrator, 'processUpdate'));
    }

    public function testConstructorParameters()
    {
        $reflection = new ReflectionClass(UpdateOrchestrator::class);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();

        $this->assertCount(5, $parameters);
        $this->assertEquals(BackupService::class, $parameters[0]->getType()->getName());
        $this->assertEquals(DownloadService::class, $parameters[1]->getType()->getName());
        $this->assertEquals(FileService::class, $parameters[2]->getType()->getName());
        $this->assertEquals(ComposerService::class, $parameters[3]->getType()->getName());

        // Check the type of the fifth parameter
        $fifthParamType = $parameters[4]->getType();
        $this->assertTrue($fifthParamType->allowsNull());
        $this->assertEquals('callable', $fifthParamType->getName());
    }

    public function testGetUpdateUrlMethod()
    {
        $updateOrchestrator = $this->getMockBuilder(UpdateOrchestrator::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $method = new \ReflectionMethod(UpdateOrchestrator::class, 'getUpdateUrl');
        $method->setAccessible(true);

        $releaseData = ['download_url' => 'https://example.com/update.zip'];
        $result = $method->invoke($updateOrchestrator, $releaseData);

        $this->assertEquals('https://example.com/update.zip', $result);
    }

    public function testGetUpdateUrlMethodThrowsException()
    {
        $updateOrchestrator = $this->getMockBuilder(UpdateOrchestrator::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $method = new \ReflectionMethod(UpdateOrchestrator::class, 'getUpdateUrl');
        $method->setAccessible(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No update available.');

        $releaseData = [];
        $method->invoke($updateOrchestrator, $releaseData);
    }
}
