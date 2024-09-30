# Laravel Auto Updater

A robust Laravel package facilitating automatic updates from GitHub, GitLab, Bitbucket, or custom repositories for your Laravel applications.
**Supports Laravel version 8 and above.**

## Table of Contents

- [Laravel Auto Updater](#laravel-auto-updater)
  - [Table of Contents](#table-of-contents)
  - [Features](#features)
  - [Installation](#installation)
  - [Configuration](#configuration)
    - [Environment Variables](#environment-variables)
    - [Config File](#config-file)
    - [Excluding Items from Updates](#excluding-items-from-updates)
    - [Set Middleware in the Config File](#set-middleware-in-the-config-file)
    - [Application Version](#application-version)
  - [Usage](#usage)
    - [Update Check Command](#update-check-command)
    - [Update Initiate Command](#update-initiate-command)
    - [Scheduling Updates](#scheduling-updates)
    - [Modified Files Warning](#modified-files-warning)
  - [Custom Update URL](#custom-update-url)
  - [API Endpoints](#api-endpoints)
  - [Blade Component](#blade-component)
  - [Contribution Guidelines](#contribution-guidelines)
  - [License](#license)

## Features

- Multi-source support: Update from GitHub, GitLab, Bitbucket, or custom repositories
- Simple configuration via environment variables and config file
- Built-in commands for update checks and initiation
- Exclusion of sensitive files/folders from updates
- Comprehensive error handling and logging
- Version tracking through `composer.json`
- Global Blade component for easy integration
- API endpoints for programmatic update management
- Configurable middleware for API security

## Installation

Install the package via Composer:

```bash
composer require anisaronno/laravel-auto-updater
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=auto-updater-config
```

This creates `auto-updater.php` in your `config` directory.

Optionally, publish assets and views:

```bash
php artisan vendor:publish --tag=auto-updater-assets
php artisan vendor:publish --tag=auto-updater-views
```

## Configuration

### Environment Variables

Add these to your `.env` file:

```dotenv
RELEASE_URL=https://github.com/anisAronno/laravel-starter
PURCHASE_KEY=your_optional_purchase_key
```

- `RELEASE_URL`: Your repository's release URL
- `PURCHASE_KEY`: (Optional) For authenticated APIs or private repos

### Config File

The `config/auto-updater.php` file contains important settings:

- **Repository Configuration**: The file uses `ReleaseProviderFactory` to create an appropriate adapter based on your `RELEASE_URL`.
- **Excluded Items**: Define files and folders to exclude from updates.
- **Middleware**: Specify which middleware to apply to the auto-updater's API endpoints.

### Excluding Items from Updates

Edit the `exclude_items` array in `config/auto-updater.php`:

```php
"exclude_items" => [
    '.env',
    '.git',
    'storage',
    'node_modules',
    'vendor',
    // Add custom exclusions here
],
```

### Set Middleware in the Config File

To configure the middleware, edit the `middleware` array in the `config/auto-updater.php` file:

```php
"middleware" => ['web'],
```

By default, the middleware is set to `web`.

### Application Version

Specify your app's version in `composer.json`:

```json
{
  "version": "1.0.0"
}
```

After making configuration changes, refresh the config cache:

```bash
php artisan config:cache
```

## Usage

### Update Check Command

Check for available updates:

```bash
php artisan update:check
```

### Update Initiate Command

Start the update process:

```bash
php artisan update:initiate
```

### Scheduling Updates

Add to `app/Console/Kernel.php` (Laravel 10 and below):

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('update:initiate')->daily();
}
```

For Laravel 11+, add to `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('update:initiate')->daily();
```

### Modified Files Warning

The updater warns about modified project files, excluding `.env` and `storage/`.

## Custom Update URL

For custom update sources, ensure your API returns:

```json
{
  "version": "1.0.0",
  "download_url": "https://example.com/api/v1/release",
  "changelog": "Your changelog text"
}
```

## API Endpoints

Access these endpoints for programmatic updates:

- Check for updates: `GET /api/auto-updater/check`
- Initiate update: `POST /api/auto-updater/update`

These endpoints are protected by the middleware specified in the config file.

## Blade Component

Use the global component anywhere in your views:

```blade
<x-auto-updater />
```

## Contribution Guidelines

We welcome contributions! Please see our [Contribution Guide](https://github.com/anisAronno/laravel-auto-updater/blob/develop/CONTRIBUTING.md) for details.

## License

This package is open-source software licensed under the [MIT License](https://opensource.org/licenses/MIT).
