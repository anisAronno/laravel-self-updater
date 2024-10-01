<?php 
namespace AnisAronno\LaravelAutoUpdater\Services\VCSProvider;
use AnisAronno\LaravelAutoUpdater\Contracts\VCSProviderInterface;
use AnisAronno\LaravelAutoUpdater\Services\ApiRequestService;
use InvalidArgumentException;

/**
 * Class AbstractVCSProvider
 * 
 * Abstract class for the VCS provider.
 */
abstract class AbstractVCSProvider implements VCSProviderInterface
{
    /**
     * The release URL.
     *
     * @var string
     */
    protected string $releaseUrl;

    /**
     * AbstractVCSProvider constructor.
     *
     * @param string $releaseUrl
     */
    public function __construct(string $releaseUrl)
    {
        $this->releaseUrl = $releaseUrl;
    }

    /**
     * Get the API URL.
     *
     * @return string
     */
    abstract protected function getApiUrl(): string;

    /**
     * Parse the release data.
     *
     * @param array $data
     * @return array
     */
    abstract protected function parseReleaseData(array $data): array;

    /**
     * Fetch the release data.
     *
     * @param string|null $version
     * @return array
     */
    protected function fetchReleaseData(?string $version = null): array
    {
        $url = $this->buildApiUrl($version);
        $response = $this->makeApiRequest($url);
        return $this->parseReleaseData($response);
    }

    /**
     * Build the API URL.
     *
     * @param string|null $version
     * @return string
     */
    abstract protected function buildApiUrl(?string $version): string;

    /**
     * Make an API request.
     *
     * @param string $url
     * @return array
     */
    protected function makeApiRequest(string $url): array
    {
        $response = ApiRequestService::get($url);

        if ($response->failed()) {
            return [];
        }

        return $response->json();
    }

    /**
     * Get the latest release.
     *
     * @return array
     */
    public function getLatestRelease(): array
    {
        return $this->fetchReleaseData();
    }

    /**
     * Get the release by version.
     *
     * @param string $version
     * @return array
     */
    public function getReleaseByVersion(string $version): array
    {
        return $this->fetchReleaseData($version);
    }

    /**
     * Extract the username and repository name from the release URL.
     *
     * @return array The username and repository name.
     * @throws \InvalidArgumentException If the release URL is invalid.
     */
    protected function extractUserAndRepo(): array
    {
        $parsedUrl = parse_url($this->releaseUrl);
        $path = trim($parsedUrl['path'], '/');
        $parts = explode('/', $path);
    
        if (count($parts) !== 2) {
            throw new InvalidArgumentException("Invalid repository URL: {$this->releaseUrl}");
        }
    
        return $parts;
    }
    
}