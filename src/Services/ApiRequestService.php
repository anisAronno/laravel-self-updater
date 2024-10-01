<?php

namespace AnisAronno\LaravelAutoUpdater\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Class ApiRequestService
 *
 * This class provides methods to make API requests.
 */
class ApiRequestService
{
    /**
     * Static method to make a GET request.
     *
     * @param  string  $url  The URL to make the request to.
     * @param  array  $headers  Optional headers to pass in the request.
     * @return Response The HTTP response.
     */
    public static function get(string $url, array $headers = []): Response
    {
        // Default User-Agent header if not provided
        $defaultHeaders = ['User-Agent' => 'PHP'];

        // Merge default headers with any provided headers
        $headers = array_merge($defaultHeaders, $headers);

        // Make the GET request using the Http facade
        return Http::withHeaders($headers)->get($url);
    }
}
