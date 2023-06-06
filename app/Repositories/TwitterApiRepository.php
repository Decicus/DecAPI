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
     * @param bool   $withReplies
     *
     * @return array
     */
    public function getTweets($username = '', $withReplies = false)
    {
        $username = strtolower(trim($username));
        $cacheKey = sprintf('twitter.tweets.%s.%s', md5($username), $withReplies ? 'with_replies' : 'without_replies');

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $request = $this->client->getUserTweets($username, $withReplies);
        if (isset($request['error'])) {
            throw new TwitterApiException($request['error']);
        }

        $tweets = $request['result'];
        Cache::put($cacheKey, $tweets, config('twitter.cache.tweets', 120));

        return $tweets;
    }
}
