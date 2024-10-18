# Laravel Self Updater

A robust Laravel package facilitating automatic updates from GitHub, GitLab, Bitbucket, or custom repositories for your Laravel applications.

**Supports Laravel version 10 and above.**

## Table of Contents

- [Laravel Self Updater](#laravel-self-updater)
  - [Table of Contents](#table-of-contents)
  - [Features](#features)
  - [Installation](#installation)
  - [Configuration](#configuration)
    - [Environment Variables](#environment-variables)
    - [Config File](#config-file)
    - [Excluding Items from Updates](#excluding-items-from-updates)
    - [Setting Middleware](#setting-middleware)
    - [Application Version](#application-version)
    - [Composer Dependencies](#composer-dependencies)
    - [Custom VCS Providers](#custom-vcs-providers)
  - [Usage](#usage)
    - [Checking for Updates](#checking-for-updates)
    - [Initiating Updates](#initiating-updates)
    - [Scheduling Automatic Updates](#scheduling-automatic-updates)
    - [Handling Modified Files](#handling-modified-files)
  - [Custom Update Sources](#custom-update-sources)
  - [API Integration](#api-integration)
  - [Blade Component](#blade-component)
  - [Contributing](#contributing)
  - [License](#license)

## Features

- **Multi-source Support**: Update from GitHub, GitLab, Bitbucket, or custom repositories
- **Simple Configuration**: Easy setup via environment variables and config file
- **Built-in Commands**: Convenient commands for update checks and initiation
- **File Exclusion**: Protect sensitive files/folders during updates
- **Error Handling**: Comprehensive logging and error management
- **Version Tracking**: Utilizes `composer.json` for version management
- **UI Integration**: Global Blade component for easy frontend implementation
- **API Endpoints**: Programmatic update management
- **Security**: Configurable middleware for API protection
- **Composer Integration**: Optional management of Composer dependencies during updates
- **Extensibility**: Support for custom VCS providers

## Installation

1. Install the package via Composer:
   ```bash
   composer require anisaronno/laravel-self-updater
   ```

2. Publish the configuration file:
   ```bash
   php artisan vendor:publish --tag=self-updater-config
   ```
   This creates `self-updater.php` in your `config` directory.

3. (Optional) Publish assets and views:
   ```bash
   php artisan vendor:publish --tag=self-updater-assets
   php artisan vendor:publish --tag=self-updater-views
   ```

## Configuration

### Environment Variables

Add these to your `.env` file:

```dotenv
RELEASE_URL=https://github.com/anisAronno/laravel-starter
LICENSE_KEY=your_optional_purchase_key
```

- `RELEASE_URL`: Your repository's release URL
- `LICENSE_KEY`: (Optional) For authenticated APIs or private repos

### Config File

The `config/self-updater.php` file contains important settings:

1. **Repository Configuration**: Uses `VCSProviderFactory` to create an appropriate adapter based on your `RELEASE_URL`.
2. **Excluded Items**: Define files and folders to exclude from updates.
3. **Middleware**: Specify which middleware to apply to the self-updater's API endpoints.
4. **Composer Dependencies**: Configure whether to run Composer install or update during the update process.

### Excluding Items from Updates

Edit the `exclude_items` array in `config/self-updater.php`:

```php
"exclude_items" => [
    '.env',
    '.git',
    'storage',
    'node_modules',
    'vendor',
    // Add your custom exclusions here
],
```

### Setting Middleware

Configure the middleware in `config/self-updater.php`:

```php
"middleware" => ['web'],
```

### Application Version

Specify your app's version in `composer.json`:

```json
{
  "version": "1.0.0"
}
```

### Composer Dependencies

Configure Composer behavior during updates in `config/self-updater.php`:

```php
'require_composer_install' => false,
'require_composer_update' => false,
```

Set these to `true` to run Composer install or update respectively during the update process.

### Custom VCS Providers

Extend functionality with custom VCS providers:

```php
use AnisAronno\LaravelSelfUpdater\Services\VCSProvider\VCSProviderFactory;

// Register a new provider
VCSProviderFactory::registerProvider('custom-vcs.com', YourCustomVCSProvider::class);

// Remove a provider
VCSProviderFactory::removeProvider('custom-vcs.com');

// Check if a provider is registered
$isRegistered = VCSProviderFactory::hasProvider('custom-vcs.com');

// Get all registered providers
$providers = VCSProviderFactory::getProviders();
```

Ensure your custom provider implements `VCSProviderInterface`.

After configuration changes, refresh the config cache:

```bash
php artisan config:cache
```

## Usage

### Checking for Updates

Run the following command to check for available updates:

```bash
php artisan update:check
```

### Initiating Updates

To start the update process, use:

```bash
php artisan update:initiate
```

### Scheduling Automatic Updates

For Laravel 10, add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('update:initiate')->daily();
}
```

For Laravel 11+, add to `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('update:initiate')->dailyAt('01:00');
```

### Handling Modified Files

The updater will warn about modified project files, excluding `.env` and `storage/`.

## Custom Update Sources

For custom update sources, ensure your API returns:

```json
{
  "version": "1.0.0",
  "download_url": "https://example.com/api/v1/release",
  "release_date": "01-02-2024",
  "changelog": "Your changelog text"
}
```

## API Integration

Access these endpoints for programmatic updates:

- Check for updates: `GET /api/self-updater/check`
- Initiate update: `POST /api/self-updater/update`

These endpoints are protected by the middleware specified in the config file.

## Blade Component

Use the global component in your views:

```blade
<x-self-updater />
```

## Contributing

We welcome contributions! Please see our [Contribution Guide](https://github.com/anisAronno/laravel-self-updater/blob/develop/CONTRIBUTING.md) for details.

## License

This package is open-source software licensed under the [MIT License](https://opensource.org/licenses/MIT).