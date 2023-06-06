<?php

namespace App\Services;

use GuzzleHttp\Client as HttpClient;

class TwitterApiClient
{
    /**
     * Base URL for the Twitter scraping API.
     *
     * @var string
     */
    protected $baseUrl = null;

    /**
     * An instance of GuzzleHttp\Client
     *
     * @var GuzzleHttp\Client
     */
    private $client;

    public function __construct(HttpClient $client)
    {
        $this->client = $client;
        $this->baseUrl = env('TWITTER_SCRAPE_API', '');
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

        $response = $this->client->request('GET', $this->baseUrl . $url, $clientParams);
        return json_decode($response->getBody(), true);
    }

    public function getUserTweets($user = '', $withReplies = false)
    {
        $url = '/user';
        $parameters = [
            'username' => $user,
        ];

        if ($withReplies) {
            $parameters['withReplies'] = '1';
        }

        $response = $this->get($url, $parameters);

        if (isset($response['error'])) {
            return $response;
        }

        return $response;
    }
}
