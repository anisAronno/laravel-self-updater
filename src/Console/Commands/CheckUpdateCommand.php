<?php

namespace AnisAronno\LaravelAutoUpdater\Console\Commands;

use AnisAronno\LaravelAutoUpdater\Services\ReleaseService;
use Illuminate\Console\Command;

class CheckUpdateCommand extends Command
{
    protected $signature = 'update:check';

    protected $description = 'Check for available updates for the project';

    protected ReleaseService $releaseService;

    public function __construct(ReleaseService $releaseService)
    {
        parent::__construct();
        $this->releaseService = $releaseService;
    }

    public function handle()
    {
        try {
            // Get the current version and latest release data
            $currentVersion = $this->releaseService->getCurrentVersion();
            $latestRelease = $this->releaseService->collectReleaseData();

            if (empty($latestRelease)) {
                $this->error('Failed to fetch the latest release data.');

                return Command::SUCCESS;
            }

            $latestVersion = ! empty($latestRelease['version']) ? ltrim($latestRelease['version'], 'v') : 'Not found';
            $changelog = $latestRelease['changelog'] ?? 'No changelog available';

            // Compare the current version with the latest release
            if (version_compare($latestVersion, $currentVersion, '>')) {
                $this->info('<fg=yellow;bg=black;options=bold>🚀  Update Available! 🚀</>', 'info');
                $this->line('Current Version: '.$currentVersion);
                $this->line('Latest Version: '.$latestVersion);
                $this->line('Changelog: '.PHP_EOL.$changelog);
            } else {
                $this->info('<fg=green>✅ Your project is up to date!</>');
            }

            return Command::SUCCESS;
        } catch (\Throwable $th) {
            $this->error('An error occurred while checking for updates: '.$th->getMessage());

            return Command::FAILURE;
        }
    }
}
