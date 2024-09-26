<?php

namespace AnisAronno\LaravelAutoUpdater\Console\Commands;

use AnisAronno\LaravelAutoUpdater\Services\UpdateOrchestrator;
use AnisAronno\LaravelAutoUpdater\Services\VersionService;
use Illuminate\Console\Command;

class UpdateInitiateCommand extends Command
{
    protected $signature = 'update:initiate {version?}';
    protected $description = 'Initiate project update to the latest version or a specific version.';

    protected $versionService;
    protected $updateOrchestrator;

    public function __construct(VersionService $versionService, UpdateOrchestrator $updateOrchestrator)
    {
        parent::__construct();
        $this->versionService = $versionService;
        $this->updateOrchestrator = $updateOrchestrator;
    }

    public function handle()
    {
        try {
            $version = $this->argument('version');
            $this->info($version ? "Initiating update for version: $version" : 'Initiating update for the latest version.');

            $releaseData = $this->versionService->fetchReleaseData($version);

            if (empty($releaseData) || is_null($releaseData['version']) || is_null($releaseData['download_url'])) {
                $this->error('No update available.');
                return Command::SUCCESS;
            }

            $this->info('Update process has been started.');

            $this->updateOrchestrator->processUpdate($releaseData, $this);
            $this->info('Update process has been completed successfully.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Update failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
