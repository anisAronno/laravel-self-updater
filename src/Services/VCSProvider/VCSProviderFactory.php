<?php

namespace AnisAronno\LaravelAutoUpdater\Services\VCSProvider;

use AnisAronno\LaravelAutoUpdater\Contracts\VCSProviderInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;
use RuntimeException;

class VCSProviderFactory
{
    /**
     * Map of VCS providers.
     *
     * @var array
     */
    protected static array $providers = [
        'github.com' => GitHubProvider::class,
        'gitlab.com' => GitLabProvider::class,
        'bitbucket.org' => BitbucketProvider::class,
    ];

    /**
     * Create a VCS provider instance.
     *
     * @param string|null $releaseUrl
     * @return VCSProviderInterface
     * @throws InvalidArgumentException|RuntimeException
     */
    public static function create(?string $releaseUrl): VCSProviderInterface
    {
        try {
            self::validateReleaseUrl($releaseUrl);

            foreach (self::$providers as $domain => $providerClass) {
                if (str_contains($releaseUrl, $domain)) {
                    return new $providerClass($releaseUrl);
                }
            }

            // If no matching provider found, use CustomProvider
            return new CustomProvider($releaseUrl);
        } catch (InvalidArgumentException $e) {
            Log::error("Invalid release URL: {$e->getMessage()}");

            throw $e;
        } catch (\Throwable $e) {
            Log::error("Error creating VCS provider: {$e->getMessage()}");

            throw new RuntimeException("Unable to create VCS provider: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Validate the release URL.
     *
     * @param string|null $releaseUrl
     * @throws InvalidArgumentException
     */
    protected static function validateReleaseUrl(?string $releaseUrl): void
    {
        if (empty($releaseUrl)) {
            throw new InvalidArgumentException('Release URL is empty');
        }

        if (! URL::isValidUrl($releaseUrl)) {
            throw new InvalidArgumentException("Invalid release URL format: $releaseUrl");
        }
    }

    /**
     * Register a new VCS provider.
     *
     * @param string $domain
     * @param string $providerClass
     * @return void
     * @throws InvalidArgumentException
     */
    public static function registerProvider(string $domain, string $providerClass): void
    {
        try {
            if (isset(self::$providers[$domain])) {
                throw new InvalidArgumentException("Provider already registered for domain: $domain");
            }

            if (! class_exists($providerClass)) {
                throw new InvalidArgumentException("Provider class does not exist: $providerClass");
            }

            if (! is_subclass_of($providerClass, VCSProviderInterface::class)) {
                throw new InvalidArgumentException("Provider class must implement VCSProviderInterface: $providerClass");
            }

            self::$providers[$domain] = $providerClass;
        } catch (\Throwable $e) {
            Log::error("Error registering provider: {$e->getMessage()}");

            throw new InvalidArgumentException("Failed to register provider: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Get all registered providers.
     *
     * @return array
     */
    public static function getProviders(): array
    {
        return self::$providers;
    }

    /**
     * Remove a registered provider.
     *
     * @param string $domain
     * @return bool
     */
    public static function removeProvider(string $domain): bool
    {
        if (isset(self::$providers[$domain])) {
            unset(self::$providers[$domain]);

            return true;
        }

        return false;
    }

    /**
     * Check if a provider is registered for a given domain.
     *
     * @param string $domain
     * @return bool
     */
    public static function hasProvider(string $domain): bool
    {
        return isset(self::$providers[$domain]);
    }
}
