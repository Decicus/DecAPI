<?php

namespace App\Services;

use GuzzleHttp\Client as HttpClient;

class BttvApiClient
{
    /**
     * Base URL for the BetterTTV API (version 3).
     *
     * @var string
     */
    protected $baseUrl = 'https://api.betterttv.net/3';

    /**
     * An instance of GuzzleHttp\Client
     *
     * @var GuzzleHttp\Client
     */
    private $client;

    public function __construct(HttpClient $client)
    {
        $this->client = $client;
    }

    /**
     * Sends a GET request to a BetterTTV API endpoint
     * Returns the decoded JSON response.
     *
     * @param string $url API endpoint (e.g. /users/{id})
     * @param array $parameters Query (HTTP GET) parameters to pass along with the request.
     * @param array $headers Extra HTTP headers to send with the request.
     *
     * @return array
     */
    public function get($url = '', $parameters = [], $headers = [])
    {
        $clientParams = [
            'headers' => $headers,
            'query' => $parameters,
            // Forward HTTP responses on API errors.
            'http_errors' => false,
        ];

        // Pass custom user agent
        if (!isset($clientParams['headers']['User-Agent'])) {
            $clientParams['headers']['User-Agent'] = env('DECAPI_USER_AGENT', 'DecAPI/1.0.0 (https://github.com/Decicus/DecAPI)');
        }

        $response = $this->client->request('GET', $this->baseUrl . $url, $clientParams);
        return json_decode($response->getBody(), true);
    }
}
