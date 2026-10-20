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
     * Whether a request can fall back to other tokens if `$authToken` is rejected (401).
     * Only set this for tokens that were picked to avoid app token rate limits, never for tokens that a request depends on.
     *
     * @var boolean
     */
    private $allowTokenFallback = false;

    /**
     * Callable that returns a random user access token (`['id' => ..., 'token' => ...]`) or null.
     * It receives an array of user IDs that should be skipped.
     *
     * @var callable|null
     */
    private $tokenPoolResolver = null;

    /**
     * Indicates whether the current token is an app token.
     *
     * @var boolean
     */
    private $isAppToken = false;

    /**
     * Where the token of the current request came from: `app`, `user` (set via `setAuthToken()`) or `pool`.
     *
     * @var string
     */
    private $tokenSource = 'app';

    /**
     * Endpoints that only return public data, where any valid user access token can replace the app access token.
     * Endpoints where the result depends on who the token belongs to (e.g. `/subscriptions`) must never be added here.
     *
     * @var array
     */
    const POOLED_ENDPOINTS = ['/streams', '/users', '/videos', '/channels', '/chat/emotes', '/teams'];

    /**
     * Cache key prefix for pooled user tokens that were rejected with a 401, followed by the user ID.
     */
    const POOL_INVALID_CACHE_PREFIX = 'TWITCH_POOL_TOKEN_INVALID_';

    /**
     * Maximum number of attempts for a single request when user tokens are rejected.
     * The last attempt always uses the app token.
     */
    const MAX_ATTEMPTS = 3;

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
     * @param string|null $token
     * @param boolean $allowFallback Allow the request to retry with other tokens if this one is rejected (401).
     *
     * @return void
     */
    public function setAuthToken($token, $allowFallback = false)
    {
        $this->authToken = $token;
        $this->allowTokenFallback = $allowFallback;
    }

    /**
     * Sets the callable used to pick a random user access token for public endpoints,
     * instead of using the app access token.
     *
     * @param callable $resolver
     *
     * @return void
     */
    public function setTokenPoolResolver(callable $resolver)
    {
        $this->tokenPoolResolver = $resolver;
    }

    /**
     * Decides which token to use for a request attempt.
     * Sets `$isAppToken` and `$tokenSource` accordingly.
     *
     * @param string $url
     * @param int $attempt
     * @param boolean $skipAuthToken
     * @param array $failedPoolIds
     *
     * @return array [token, pooled user ID or null]
     */
    private function resolveToken($url, $attempt, $skipAuthToken, $failedPoolIds)
    {
        $this->isAppToken = false;

        if (!$skipAuthToken && !empty($this->authToken)) {
            $this->tokenSource = 'user';
            return [$this->authToken, null];
        }

        if ($attempt < self::MAX_ATTEMPTS && $this->tokenPoolResolver !== null && in_array($url, self::POOLED_ENDPOINTS, true)) {
            try {
                $pooled = ($this->tokenPoolResolver)($failedPoolIds);
            }
            catch (\Exception $ex)
            {
                // Never fail a request because the pool is unavailable, fall back to the app token instead.
                Log::error('Unable to get a pooled user token.');
                Log::error($ex);
                $pooled = null;
            }

            if (!empty($pooled)) {
                $this->tokenSource = 'pool';
                return [$pooled['token'], $pooled['id']];
            }
        }

        $this->isAppToken = true;
        $this->tokenSource = 'app';

        return [$this->getAppToken(), null];
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
            /**
             * Guzzle's array-valued `query` option encodes array parameters as `id[0]=x&id[1]=y`,
             * which Twitch's API does not understand. Building the query string ourselves produces
             * the repeated `id=x&id=y` format Twitch expects.
             *
             * Null values are stripped first since Query::build(), unlike Guzzle's own array `query`
             * handling, would otherwise render them as bare params (e.g. `user_id`) instead of omitting them.
             */
            'query' => \GuzzleHttp\Psr7\Query::build(array_filter($parameters, fn ($value) => $value !== null)),
            // Forward HTTP responses on API errors.
            'http_errors' => false,
        ];

        $clientParams['headers']['Client-ID'] = $this->twitchClientId;

        // Strip `/` at the beginning of path.
        $endpoint = ltrim($url, '/');
        // Replace all other `/` with `_`
        $endpoint = str_replace('/', '_', $endpoint);

        $failedPoolIds = [];
        $skipAuthToken = false;
        $attempt = 0;

        do {
            $attempt++;

            // Override with token, regardless of what was previously input.
            [$token, $poolId] = $this->resolveToken($url, $attempt, $skipAuthToken, $failedPoolIds);
            $clientParams['headers']['Authorization'] = 'Bearer ' . $token;

            if ($this->datadogEnabled === true)
            {
                try {
                    Datadog::increment('twitch.helix_' . $endpoint, ['parameters' => $parameters]);
                    Datadog::increment('twitch.helix_requests', 1.0, [
                        'token_type' => $this->tokenSource,
                        'endpoint' => $endpoint,
                    ]);
                }
                catch (\Exception $ex)
                {
                    Log::error('Unable to submit Datadog metrics.');
                    Log::error($ex);
                }
            }

            $response = $this->client->request('GET', $this->baseUrl . $url, $clientParams);

            /**
             * Log at most once per 10 seconds per token source, since a burst of 429s would otherwise flood the logs.
             * Sources are throttled separately so that frequent app token 429s don't hide user token 429s.
             * `Cache::add()` only succeeds if the key doesn't already exist.
             */
            if ($response->getStatusCode() === 429 && Cache::add('TWITCH_API_RATE_LIMIT_LOG_THROTTLE_' . $this->tokenSource, true, 10)) {
                Log::warning(sprintf(
                    'Twitch API rate limit hit: %s | token: %s | Ratelimit-Limit: %s | Ratelimit-Reset: %s',
                    $url,
                    $this->tokenSource,
                    $response->getHeaderLine('Ratelimit-Limit'),
                    $response->getHeaderLine('Ratelimit-Reset')
                ));
            }

            /**
             * A rejected user token (revoked, expired etc.) shouldn't fail the request when the token wasn't required.
             * Pooled tokens are always replaceable, `$authToken` only if the caller allowed a fallback.
             * Rejected pooled tokens are skipped for an hour. Invalid users are cleaned up by `twitch:authuservalidate`.
             */
            $rejected = $response->getStatusCode() === 401
                && !$this->isAppToken
                && $attempt < self::MAX_ATTEMPTS
                && ($this->tokenSource === 'pool' || $this->allowTokenFallback);

            if ($rejected) {
                if ($this->tokenSource === 'pool') {
                    $failedPoolIds[] = $poolId;
                    Cache::put(self::POOL_INVALID_CACHE_PREFIX . $poolId, true, 3600);
                    Log::info(sprintf('Pooled user token rejected by Twitch (401), user ID: %s | %s', $poolId, $url));
                }
                else {
                    $skipAuthToken = true;
                    Log::info(sprintf('User token rejected by Twitch (401), retrying with another token: %s', $url));
                }
            }
        } while ($rejected);

        if ($this->datadogEnabled === true && $this->isAppToken) {
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
