<?php

namespace App\Services;

use GuzzleHttp\Client as HttpClient;
use App\Http\Resources\Twitch\AppToken as TwitchAppToken;
use Cache;

class TwitchApiClient
{
    /**
     * Base URL for the Twitch 'Helix' API.
     *
     * @var string
     */
    protected $baseUrl = 'https://api.twitch.tv/helix';

    /**
     * An instance of GuzzleHttp\Client
     *
     * @var GuzzleHttp\Client
     */
    private $client;

    /**
     * Twitch client ID
     *
     * @var string
     */
    private $twitchClientId = null;

    /**
     * Twitch client secret, used for authentication.
     *
     * @var string
     */
    private $twitchClientSecret = null;

    /**
     * OAuth token to use in requests where user authentication is required.
     *
     * @var string
     */
    private $authToken = null;

    public function __construct(HttpClient $client)
    {
        $this->twitchClientId = env('TWITCH_CLIENT_ID');
        $this->twitchClientSecret = env('TWITCH_CLIENT_SECRET');
        $this->client = $client;
    }

    /**
     * Retrieve the Twitch app token, renew if necessary.
     *
     * @return string
     */
    public function getAppToken()
    {
        if (!Cache::has('TWITCH_APP_TOKEN')) {
            $this->refreshAppToken();
        }

        return Cache::get('TWITCH_APP_TOKEN');
    }

    /**
     * Requests a new app token for Helix requests and puts it in cache.
     *
     * @return void
     */
    public function refreshAppToken()
    {
        $url = 'https://id.twitch.tv/oauth2/token';
        $response = $this->client->request('POST', $url, [
            'query' => [
                'client_id' => $this->twitchClientId,
                'client_secret' => $this->twitchClientSecret,
                'grant_type' => 'client_credentials',
                'scope' => null,
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        $token = TwitchAppToken::make($data)
                               ->resolve();

        Cache::put('TWITCH_APP_TOKEN', $token['access_token'], $token['expires']);
    }

    /**
     * Returns the relevant OAuth token for user authentication.
     *
     * @return string
     */
    public function getAuthToken()
    {
        return $this->authToken;
    }

    /**
     * Sets the relevant OAuth token for user authentication.
     *
     * @param string $token
     *
     * @return void
     */
    public function setAuthToken($token)
    {
        $this->authToken = $token;
    }

    /**
     * Sends a GET request to a Helix API endpoint.
     * Returns the decoded JSON response.
     *
     * @param string $url API endpoint (e.g. /streams)
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

        // Override with token, regardless of what was previously input.
        $token = $this->getAuthToken() ?? $this->getAppToken();
        $clientParams['headers']['Authorization'] = 'Bearer ' . $token;
        $clientParams['headers']['Client-ID'] = $this->twitchClientId;

        $response = $this->client->request('GET', $this->baseUrl . $url, $clientParams);
        return json_decode($response->getBody(), true);
    }
}
