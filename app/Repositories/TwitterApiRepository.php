<?php

namespace App\Repositories;

use App\Services\TwitterApiClient;

use Exception;
use App\Exceptions\TwitterApiException;

use Cache;

class TwitterApiRepository
{
    /**
     * @var App\Services\TwitterApiClient
     */
    private $client;

    public function __construct(TwitterApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Retrieves a list of tweets for a user.
     *
     * @param string $username
     *
     * @return array
     */
    public function getTweets($username = '')
    {
        $username = strtolower(trim($username));
        $cacheKey = 'twitter.tweets.' . $username;

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $request = $this->client->getUserTweets($username);
        if (isset($request['error'])) {
            throw new TwitterApiException($request['error']);
        }

        $tweets = $request['result'];
        Cache::put($cacheKey, $tweets, 120);

        return $tweets;
    }
}
