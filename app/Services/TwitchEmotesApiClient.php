<?php

namespace App\Services;

use GuzzleHttp\Client as HttpClient;

class TwitchEmotesApiClient
{
    /**
     * Base URL for V4 of TwitchEmotes.com API.
     *
     * @var string
     */
    protected $baseUrl = 'https://api.twitchemotes.com/api/v4';

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
     * Sends a GET request to the TwitchEmotes API
     * Returns the decoded JSON response.
     *
     * @param string $url API endpoint (e.g. /sets)
     * @param array $parameters Query (HTTP GET) parameters to pass along with the request.
     * @param array $headers Extra HTTP headers to send with the request.
     *
     * @return array
     */
    public function get($url = '', $parameters = [], $headers = [])
    {
        if (empty($headers['User-Agent'])) {
            $headers['User-Agent'] = env('DECAPI_USER_AGENT', 'DecAPI/1.0.0 (https://github.com/Decicus/DecAPI)');
        }

        $clientParams = [
            'headers' => $headers,
            'query' => $parameters,
            // Forward HTTP responses on API errors.
            'http_errors' => false,
        ];

        $response = $this->client->request('GET', $this->baseUrl . $url, $clientParams);
        return json_decode($response->getBody(), true);
    }
}
