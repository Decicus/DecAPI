<?php

namespace App\Services;

use App\Exceptions\TwitchApiException;
use GuzzleHttp\Client as HttpClient;
use App\Http\Resources\Twitch\AppToken as TwitchAppToken;
use App\Http\Resources\Twitch\AuthToken as TwitchAuthToken;

use App\User;
use Cache;
use Crypt;
use Datadog;
use Log;

class TwitchApiClient
{
    /**
     * Base URL for the Twitch 'Helix' API.
     *
     * @var string
     */
    protected $baseUrl = 'https://api.twitch.tv/helix';

    /**
     * URL for the Twitch OAuth token endpoint.
     *
     * @var string
     */
    protected $tokenUrl = 'https://id.twitch.tv/oauth2/token';

    /**
     * An instance of GuzzleHttp\Client
     *
     * @var \GuzzleHttp\Client
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

    /**
     * If Datadog metrics should be considered enabled.
     * See `DATADOG_ENABLED` in .env
     *
     * @var boolean
     */
    protected $datadogEnabled = false;

    public function __construct(HttpClient $client)
    {
        $this->twitchClientId = env('TWITCH_CLIENT_ID');
        $this->twitchClientSecret = env('TWITCH_CLIENT_SECRET');
        $this->client = $client;

        $this->datadogEnabled = env('DATADOG_ENABLED', false);
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
     * Refreshes the token using the refresh token.
     *
     * @param string $type
     * @param string|null $token
     *
     * @return App\Http\Resources\Twitch\AppToken|App\Http\Resources\Twitch\AuthToken
     */
    private function refreshToken($type = 'client_credentials', $token = null)
    {
        $params = [
            'client_id' => $this->twitchClientId,
            'client_secret' => $this->twitchClientSecret,
            'grant_type' => $type,
        ];

        if ($type === 'refresh_token') {
            $params['refresh_token'] = $token;
        }

        $response = $this->client->request('POST', $this->tokenUrl, [
            'http_errors' => false,
            'form_params' => $params,
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            $body = (string) $response->getBody();

            Log::error(sprintf('Failed to refresh token: %s | %s', $statusCode, $body));
            throw new TwitchApiException('Failed to refresh token.', $statusCode);
        }

        $data = json_decode($response->getBody(), true);

        $token = TwitchAuthToken::make($data)
                                ->resolve();

        if ($type === 'client_credentials') {
            $token = TwitchAppToken::make($data)
                                   ->resolve();
        }

        return $token;
    }

    /**
     * Requests a new app token for Helix requests and puts it in cache.
     *
     * @return void
     */
    public function refreshAppToken()
    {
        $token = $this->refreshToken('client_credentials');
        Cache::put('TWITCH_APP_TOKEN', $token['access_token'], $token['expires']);
    }

    /**
     * Refreshes the user token using the refresh token.
     *
     * @param string $refreshToken
     *
     * @return array
     */
    public function refreshUserToken(string $refreshToken)
    {
        $token = $this->refreshToken('refresh_token', $refreshToken);
        return $token;
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

        if ($this->datadogEnabled === true)
        {
            try {
                // Strip `/` at the beginning of path.
                $endpoint = ltrim($url, '/');
                // Replace all other `/` with `_`
                $endpoint = str_replace('/', '_', $endpoint);

                Datadog::increment('twitch.helix_' . $endpoint, ['parameters' => $parameters]);
            }
            catch (\Exception $ex)
            {
                Log::error('Unable to submit Datadog metrics.');
                Log::error($ex);
            }
        }

        $response = $this->client->request('GET', $this->baseUrl . $url, $clientParams);
        if ($this->datadogEnabled === true) {
            $headers = $response->getHeaders();
            $rateLimit = $headers['Ratelimit-Remaining'] ?? null;
            if ($rateLimit !== null) {
                $rateLimit = (int) $rateLimit[0];
                try {
                    Datadog::distribution('twitch.helix_rate_limit_remaining', $rateLimit);
                }
                catch (\Exception $ex)
                {
                    Log::error('Unable to submit Datadog metrics.');
                    Log::error($ex);
                }
            }
        }

        return json_decode($response->getBody(), true);
    }
}
