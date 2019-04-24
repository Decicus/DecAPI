<?php

namespace App\Services;

use GuzzleHttp\Client as HttpClient;
use App\Http\Resources\TwitchAppToken;
use Cache;

class TwitchApiClient
{
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
    public function appToken()
    {
        if (!Cache::has('TWITCH_APP_TOKEN')) {
            $this->renewAppToken();
        }

        return Cache::get('TWITCH_APP_TOKEN');
    }

    /**
     * Requests a new app token for Helix requests and puts it in cache.
     *
     * @return void
     */
    public function renewAppToken()
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
}
