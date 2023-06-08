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
     * Normalizes a Twitter username.
     *
     * @param string $username
     *
     * @return string
     */
    private function normalizeUsername($username = '')
    {
        return strtolower(trim($username));
    }

    /**
     * Gets the cache key for caching a user's profile details.
     *
     * @param string $username
     *
     * @return string
     */
    private function getUserCacheKey($username = '')
    {
        $username = $this->normalizeUsername($username);
        return sprintf('twitter.user.%s', md5($username));
    }

    /**
     * Caches the profile details for a user.
     *
     * @param string $username
     * @param array $user
     *
     * @return void
     */
    private function cacheUserDetails($username = '', $user = [])
    {
        $cacheKey = $this->getUserCacheKey($username);
        Cache::put($cacheKey, $user, config('twitter.cache.user', 3600));
    }

    /**
     * Retrieves the profile details for a user.
     * Relies on the same Twitter scraping endpoint.
     *
     * @param string $username
     *
     * @return array
     */
    public function getUser($username = '')
    {
        $username = $this->normalizeUsername($username);
        $cacheKey = $this->getUserCacheKey($username);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $request = $this->client->getUserTweets($username);
        if (isset($request['error'])) {
            throw new TwitterApiException($request['error']);
        }

        $user = $request['user'];
        $this->cacheUserDetails($username, $user);

        return $user;
    }

    /**
     * Retrieves a list of tweets for a user.
     *
     * This method also caches the user's profile details, since they are included in the same response.
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

        // Since we already have the user details, we might as well cache them too.
        $user = $request['user'];
        $this->cacheUserDetails($username, $user);

        return $tweets;
    }
}
