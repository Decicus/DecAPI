<?php

namespace App\Repositories;

use App\Services\TwitchEmotesApiClient;

use App\Exceptions\TwitchEmotesApiException;

use App\Http\Resources\TwitchEmotes\Channel;

use Cache;

class TwitchEmotesApiRepository
{
    /**
     * @var App\Services\TwitchEmotesApiClient
     */
    private $client;

    public function __construct(TwitchEmotesApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Retrieve channel information from local cache or TwitchEmotes' API.
     * If retrieved from the API, the information is cached for 30 minutes.
     *
     * @param string $channelId
     *
     * @return array
     */
    public function channel($channelId = '')
    {
        $cacheKey = 'TWITCHEMOTES_CHANNEL_' . $channelId;

        if (Cache::has($cacheKey)) {
            $cachedChannel = Cache::get($cacheKey);

            return Channel::make($cachedChannel)
                          ->resolve();
        }

        $request = $this->client->get('/channels/' . $channelId);

        if (isset($request['error'])) {
            throw new TwitchEmotesApiException(sprintf('Error requesting TwitchEmotes channel details for %s - %s', $channelId, $request['error']));
        }

        $expire = now()->addMinutes(30);
        Cache::put($cacheKey, $request, $expire);

        return Channel::make($request)
                      ->resolve();
    }
}
