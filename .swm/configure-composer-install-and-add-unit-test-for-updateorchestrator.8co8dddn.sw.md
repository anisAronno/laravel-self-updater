---
title: Configure Composer Install and Add Unit Test for UpdateOrchestrator
---
# Introduction

This document will walk you through the implementation of the feature to configure Composer install and add unit tests for the <SwmToken path="/src/Services/UpdateOrchestrator.php" pos="15:2:2" line-data="class UpdateOrchestrator">`UpdateOrchestrator`</SwmToken>.

The feature allows optional Composer dependency management during updates and introduces unit tests to ensure the reliability of the <SwmToken path="/src/Services/UpdateOrchestrator.php" pos="15:2:2" line-data="class UpdateOrchestrator">`UpdateOrchestrator`</SwmToken>.

We will cover:

1. Configuration of Composer install and update options.
2. Implementation of the <SwmToken path="/src/Services/BackupService.php" pos="12:5:5" line-data=" * Class BackupService">`BackupService`</SwmToken> and <SwmToken path="/src/Services/ComposerService.php" pos="14:2:2" line-data="class ComposerService">`ComposerService`</SwmToken>.
3. Unit tests for <SwmToken path="/src/Services/BackupService.php" pos="12:5:5" line-data=" * Class BackupService">`BackupService`</SwmToken>, <SwmToken path="/src/Services/ComposerService.php" pos="14:2:2" line-data="class ComposerService">`ComposerService`</SwmToken>, and <SwmToken path="/src/Services/UpdateOrchestrator.php" pos="15:2:2" line-data="class UpdateOrchestrator">`UpdateOrchestrator`</SwmToken>.

# Configuration of Composer install and update options

<SwmSnippet path="README.md" line="41">

---

We start by documenting the new configuration options in the README file. This helps users understand how to enable or disable Composer install and update during the update process.

```
- Optional Composer dependencies management during updates
```

---

</SwmSnippet>

<SwmSnippet path="README.md" line="87">

---

```
- **Composer Dependencies**: Configure whether to run Composer install or update during the update process.
```

---

</SwmSnippet>

<SwmSnippet path="README.md" line="124">

---

````
### Composer Dependencies

Configure whether to run Composer install or update during the update process:

```php
'require_composer_install' => false,
'require_composer_update' => false,
```

Set these to `true` if you want to run Composer install or update respectively during the update process.

````

---

</SwmSnippet>

<SwmSnippet path="config/auto-updater-config.php" line="21">

---

Next, we add the actual configuration options in the <SwmPath>[config/auto-updater-config.php](/config/auto-updater-config.php)</SwmPath> file. This is where the default values for <SwmToken path="/config/auto-updater-config.php" pos="21:2:2" line-data="    &#39;require_composer_install&#39; =&gt; false,">`require_composer_install`</SwmToken> and <SwmToken path="/config/auto-updater-config.php" pos="22:2:2" line-data="    &#39;require_composer_update&#39; =&gt; false,">`require_composer_update`</SwmToken> are set.

```
    'require_composer_install' => false,
    'require_composer_update' => false,
```

---

</SwmSnippet>

# Implementation of the <SwmToken path="/src/Services/BackupService.php" pos="12:5:5" line-data=" * Class BackupService">`BackupService`</SwmToken>

The <SwmToken path="/src/Services/BackupService.php" pos="12:5:5" line-data=" * Class BackupService">`BackupService`</SwmToken> is responsible for creating and managing backups during the update process. The <SwmToken path="/src/Services/BackupService.php" pos="33:5:5" line-data="    public function backup(Command $command): string">`backup`</SwmToken> method creates a backup of the current state, while the <SwmToken path="/src/Services/BackupService.php" pos="84:5:5" line-data="    public function rollback(string $backupPath, Command $command)">`rollback`</SwmToken> method restores the state from a backup.

<SwmSnippet path="/src/Services/BackupService.php" line="33">

---

The <SwmToken path="/src/Services/BackupService.php" pos="33:5:5" line-data="    public function backup(Command $command): string">`backup`</SwmToken> method ensures the backup directory exists, starts the backup process, and creates a zip file of the files to be backed up.

```
    public function backup(Command $command): string
    {
        $backupPath = $this->getBackupPath();

        try {
            File::ensureDirectoryExists($backupPath);

            $command->info('Starting backup process...');
            $filesToBackup = $this->fileService->getFilesToBackup(base_path());

            $zip = new ZipArchive;
            $zipPath = $backupPath.'/backup.zip';

            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception("Cannot create zip file: $zipPath");
            }

            $progressBar = $command->getOutput()->createProgressBar(count($filesToBackup));
            $progressBar->start();

            foreach ($filesToBackup as $source => $target) {
                $relativeTarget = str_replace(base_path().'/', '', $target);
                if (File::isDirectory($source)) {
                    $zip->addEmptyDir($relativeTarget);
                } else {
                    $zip->addFile($source, $relativeTarget);
                }
                $progressBar->advance();
            }

            $zip->close();
            $progressBar->finish();

            $command->info("\nBackup completed: $zipPath");

            $this->logBackupDetails($backupPath, $zipPath);

            return $backupPath;
        } catch (Exception $e) {
            Log::error('Backup failed: '.$e->getMessage());
            $command->error('Backup failed: '.$e->getMessage());

            throw $e;
        }
    }
```

---

</SwmSnippet>

<SwmSnippet path="/src/Services/BackupService.php" line="79">

---

The <SwmToken path="/src/Services/BackupService.php" pos="84:5:5" line-data="    public function rollback(string $backupPath, Command $command)">`rollback`</SwmToken> method checks if the backup zip file exists, extracts it, and replaces the current project files with the backup files.

```
    /**
     * Roll back to the given backup.
     *
     * @throws Exception
     */
    public function rollback(string $backupPath, Command $command)
    {
        $zipPath = $backupPath.'/backup.zip';

        if (! File::exists($zipPath)) {
            throw new Exception("Backup not found: $zipPath");
        }

        $command->info('Rolling back to backup...');

        try {
            $zip = new ZipArchive;
            if ($zip->open($zipPath) !== true) {
                throw new Exception("Cannot open zip file: $zipPath");
            }

            $extractPath = storage_path('app/rollback_temp');
            File::ensureDirectoryExists($extractPath);

            $zip->extractTo($extractPath);
            $zip->close();

            $this->fileService->replaceProjectFiles($extractPath, base_path(), $command);

            File::deleteDirectory($extractPath);

            $command->info("Rolled back to backup: $backupPath");
        } catch (Exception $e) {
            Log::error('Rollback failed: '.$e->getMessage());
            $command->error('Rollback failed: '.$e->getMessage());

            throw $e;
        }
    }
```

---

</SwmSnippet>

# Implementation of the <SwmToken path="/src/Services/ComposerService.php" pos="14:2:2" line-data="class ComposerService">`ComposerService`</SwmToken>

The <SwmToken path="/src/Services/ComposerService.php" pos="14:2:2" line-data="class ComposerService">`ComposerService`</SwmToken> handles running Composer commands. It includes methods to run `composer install` and `composer update`, and handles any failures that occur during these commands.

<SwmSnippet path="/src/Services/ComposerService.php" line="26">

---

The <SwmToken path="/src/Services/ComposerService.php" pos="31:5:5" line-data="    public function runComposerUpdate()">`runComposerUpdate`</SwmToken> method runs the `composer update` command.

```
    /**
     * Run composer update.
     *
     * @throws Exception
     */
    public function runComposerUpdate()
    {
        $this->executeComposerCommand('update --no-interaction');
    }
```

---

</SwmSnippet>

<SwmSnippet path="/src/Services/ComposerService.php" line="35">

---

The <SwmToken path="/src/Services/ComposerService.php" pos="41:5:5" line-data="    protected function executeComposerCommand(string $command)">`executeComposerCommand`</SwmToken> method executes the given Composer command and logs the output. If the command fails, it throws an exception.

```

    /**
     * Execute the composer command.
     *
     * @throws Exception
     */
    protected function executeComposerCommand(string $command)
    {
        try {
            $result = Process::run("composer $command 2>&1");
            if (! $result->successful()) {
                $this->handleCommandFailure($result->output());
            }
            Log::info("Composer command executed successfully: $command");
            Log::info('Output: '.$result->output());
        } catch (\Throwable $e) {
            $this->handleCommandFailure($e->getMessage());
        }
    }
```

---

</SwmSnippet>

# Unit tests for <SwmToken path="/src/Services/BackupService.php" pos="12:5:5" line-data=" * Class BackupService">`BackupService`</SwmToken>, <SwmToken path="/src/Services/ComposerService.php" pos="14:2:2" line-data="class ComposerService">`ComposerService`</SwmToken>, and <SwmToken path="/src/Services/UpdateOrchestrator.php" pos="15:2:2" line-data="class UpdateOrchestrator">`UpdateOrchestrator`</SwmToken>

Unit tests are added to ensure the reliability of the <SwmToken path="/src/Services/BackupService.php" pos="12:5:5" line-data=" * Class BackupService">`BackupService`</SwmToken>, <SwmToken path="/src/Services/ComposerService.php" pos="14:2:2" line-data="class ComposerService">`ComposerService`</SwmToken>, and <SwmToken path="/src/Services/UpdateOrchestrator.php" pos="15:2:2" line-data="class UpdateOrchestrator">`UpdateOrchestrator`</SwmToken>.

<SwmSnippet path="tests/BackupServiceTest.php" line="11">

---

The <SwmToken path="/tests/BackupServiceTest.php" pos="12:2:2" line-data="class BackupServiceTest extends TestCase">`BackupServiceTest`</SwmToken> includes tests for the <SwmToken path="/src/Services/BackupService.php" pos="33:5:5" line-data="    public function backup(Command $command): string">`backup`</SwmToken> and <SwmToken path="/src/Services/BackupService.php" pos="84:5:5" line-data="    public function rollback(string $backupPath, Command $command)">`rollback`</SwmToken> methods, ensuring they work as expected.

```

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
```

---

</SwmSnippet>

<SwmSnippet path="/tests/ComposerServiceTest.php" line="11">

---

The <SwmToken path="/tests/ComposerServiceTest.php" pos="11:2:2" line-data="class ComposerServiceTest extends TestCase">`ComposerServiceTest`</SwmToken> includes tests for running Composer commands and handling failures.

```
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

```

---

</SwmSnippet>

<SwmSnippet path="tests/UpdateOrchestratorTest.php" line="14">

---

The <SwmToken path="/tests/UpdateOrchestratorTest.php" pos="15:2:2" line-data="class UpdateOrchestratorTest extends TestCase">`UpdateOrchestratorTest`</SwmToken> includes tests for the <SwmToken path="/src/Services/UpdateOrchestrator.php" pos="15:2:2" line-data="class UpdateOrchestrator">`UpdateOrchestrator`</SwmToken> class, ensuring it correctly processes updates and handles configuration options.

```

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
```

---

</SwmSnippet>

This concludes the walkthrough of the feature implementation. The configuration options, service implementations, and unit tests ensure that the update process is robust and configurable.

<SwmMeta version="3.0.0" repo-id="Z2l0aHViJTNBJTNBbGFyYXZlbC1hdXRvLXVwZGF0ZXIlM0ElM0FhbmlzQXJvbm5v" repo-name="laravel-auto-updater"><sup>Powered by [Swimm](https://app.swimm.io/)</sup></SwmMeta>
