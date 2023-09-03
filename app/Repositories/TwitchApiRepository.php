<?php

namespace App\Repositories;

use App\Services\TwitchApiClient;

use App\Exceptions\TwitchApiException;
use App\Exceptions\TwitchFormatException;

use App\Http\Resources\Twitch as Resource;

use App\CachedTwitchUser;
use Cache;

class TwitchApiRepository
{
    /**
     * @var TwitchApiClient
     */
    private $client;

    public function __construct(TwitchApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Set the OAuth token that should be used for requests.
     *
     * @param string $token
     *
     * @return void
     */
    public function setToken($token = '')
    {
        $this->client->setAuthToken($token);
    }

    /**
     * Sends a request to the `channels` endpoint: https://dev.twitch.tv/docs/api/reference#get-channel-information
     *
     * @param array $fields
     *
     * @return array
     * @throws TwitchApiException
     */
    public function channels($fields = [])
    {
        $request = $this->client->get('/channels', $fields);

        if (isset($request['error'])) {
            extract($request);
            throw new TwitchApiException(sprintf('%d: %s - %s', $status, $error, $message), $status);
        }

        $channels = collect($request['data']);
        return Resource\ChannelCollection::make($channels)
                                         ->resolve();
    }

    /**
     * Retrieves channel information for multiple channels by their unique ID.
     *
     * @param array $ids
     * @return array
     *
     * @throws TwitchApiException
     * @throws TwitchFormatException
     */
    public function channelsByIds($ids = [])
    {
        if (!is_array($ids)) {
            throw new TwitchFormatException('Array expected, got: ' . gettype($ids));
        }

        return $this->channels(['broadcaster_id' => $ids]);
    }

    /**
     * Retrieve channel information for a single channel by their unique ID.
     *
     * @param string $id
     * @return array
     *
     * @throws TwitchApiException
     * @throws TwitchFormatException
     */
    public function channelById($id = '')
    {
        if (!is_string($id) && !is_int($id))
        {
            throw new TwitchFormatException('String or int expected, got: ' . gettype($id));
        }

        $channels = $this->channelsByIds([$id]);
        return $channels[0];
    }

    /**
     * Sends a request to the `channel emotes` endpoint: https://dev.twitch.tv/docs/api/reference#get-channel-emotes
     *
     * @param array $fields
     *
     * @return array
     * @throws TwitchApiException
     */
    public function channelEmotes($fields = [])
    {
        $request = $this->client->get('/chat/emotes', $fields);

        if (isset($request['error'])) {
            extract($request);
            throw new TwitchApiException(sprintf('%d: %s - %s', $status, $error, $message), $status);
        }

        $channels = collect($request['data']);
        return Resource\EmoteCollection::make($channels)
                                       ->resolve();
    }

    /**
     * Retrieve channel emote information by the channel's ID.
     *
     * @param string $id
     * @return array
     * @throws App\Exceptions\TwitchApiException|App\Exceptions\TwitchFormatException
     */
    public function channelEmotesById($id = '')
    {
        if (!is_string($id) && !is_int($id))
        {
            throw new TwitchFormatException('String or int expected, got: ' . gettype($id));
        }

        /**
         * Return cached emotes for this channel, if any.
         */
        $cacheKey = sprintf('TWITCH_API_CHANNEL_EMOTES_%s', $id);
        if (Cache::has($cacheKey)) {
            $cachedEmotes = Cache::get($cacheKey);
            return $cachedEmotes;
        }

        $emotes = $this->channelEmotes(['broadcaster_id' => $id]);

        /**
         * Cache for amount of minutes as specified in config.
         */
        $cacheExpire = now()->addMinutes(config('twitch.cache.channel_emotes'));
        Cache::put($cacheKey, $emotes, $cacheExpire);

        return $emotes;
    }

    /**
     * Retrieve channel follower information.
     *
     * If a user ID is specified, a valid user access token with `moderator:read:followers`,
     * either the broadcaster or a moderator for the relevant channel (broadcaster ID) must be specified via `setToken()`.
     *
     * @param string|int $broadcasterId
     * @param string|int|null $userId
     *
     * @return array
     * @throws App\Exceptions\TwitchApiException|App\Exceptions\TwitchFormatException
     */
    public function channelFollowers($broadcasterId, $userId = null)
    {
        if (!is_string($broadcasterId) && !is_int($broadcasterId)) {
            throw new TwitchFormatException('Broadcaster ID - String or int expected, got: ' . gettype($broadcasterId));
        }

        if (!empty($userId) && !is_string($userId) && !is_int($userId)) {
            throw new TwitchFormatException('User ID - String or int expected, got: ' . gettype($userId));
        }

        $cacheKey = sprintf('TWITCH_API_CHANNEL_FOLLOWERS_%s', $broadcasterId);
        if (!empty($userId)) {
            $cacheKey = sprintf('TWITCH_API_CHANNEL_FOLLOWERS_%s_%s', $broadcasterId, $userId);
        }

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $params = [
            'broadcaster_id' => $broadcasterId,
            'first' => 100,
        ];

        if (!empty($userId)) {
            $params['user_id'] = $userId;
        }

        $request = $this->client->get('/channels/followers', $params);
        $result = Resource\ChannelFollowers::make($request)
                                        ->resolve();

        /**
         * TODO: Use a separate cache time config.
         * Should be increased if support for streamer/mod token access is added.
         */
        Cache::put($cacheKey, $result, config('twitch.cache.followcount'));
        return $result;
    }

    /**
     * Get videos (VODs, highlights etc.) of the specified channel.
     *
     * @param string $userId
     * @param string $type Type of video (all, upload, archive, highlight). Default: `all`
     * @param integer $first
     *
     * @return array
     */
    public function channelVideos($userId = '', $type = 'all', $first = 20)
    {
        $cacheKey = sprintf('TWITCH_API_CHANNEL_VIDEOS_%s_%s_%s', $userId, $type, $first);
        if (Cache::has($cacheKey)) {
            $cachedVideos = Cache::get($cacheKey);
            return $cachedVideos;
        }

        $params = [
            'user_id' => $userId,
            'type' => $type,
            'first' => $first,
        ];

        $videos = $this->videos($params);
        Cache::put($cacheKey, $videos, config('twitch.cache.channel_videos'));

        return $videos;
    }

    /**
     * Retrieves the channel's followers, as well as the total number of followers.
     *
     * @param string $toId Twitch user ID of the channel
     * @param integer $first Maximum number of objects to return. Maximum: 100. Default: 20.
     * @param string $after Cursor used for pagination.
     *
     * @return array
     * @throws TwitchApiException
     */
    public function followsChannel($toId = '', $first = 20, $after = null)
    {
        $params = [
            'to_id' => $toId,
            'first' => $first,
            'after' => $after,
        ];

        $request = $this->client->get('/users/follows', $params);

        if (isset($request['error'])) {
            extract($request);
            throw new TwitchApiException(sprintf('%d: %s - %s', $status, $error, $message), $status);
        }

        $followData = collect($request);

        return Resource\Follow::make($followData)
                              ->resolve();
    }

    /**
     * Returns the follow relationship between a channel ($toId) and user ($fromId).
     *
     * @param string $toId User ID of the channel
     * @param string $fromId User ID of the user.
     *
     * @return array
     * @throws TwitchApiException
     */
    public function followRelationship($toId = '', $fromId = '')
    {
        $params = [
            'to_id' => $toId,
            'from_id' => $fromId,
        ];

        $request = $this->client->get('/users/follows', $params);

        if (isset($request['error'])) {
            extract($request);
            throw new TwitchApiException(sprintf('%d: %s - %s', $status, $error, $message), $status);
        }

        $followData = collect($request['data']);
        return Resource\FollowUserCollection::make($followData)
                                            ->resolve();
    }

    /**
     * Retrieves a single Twitch livestream by their unique user ID.
     *
     * @param string|int $id ID can be a string or an int (for legacy reasons).
     *
     * @return array
     * @throws TwitchFormatException
     * @throws TwitchApiException
     */
    public function streamById($id = '')
    {
        if (!is_string($id) && !is_int($id))
        {
            throw new TwitchFormatException('String or int expected, got: ' . gettype($id));
        }

        $cacheKey = sprintf('TWITCH_API_STREAM_BY_ID_%s', $id);
        if (Cache::has($cacheKey)) {
            $cachedStream = Cache::get($cacheKey);
            return $cachedStream;
        }

        $streams = $this->streamsByIds([$id]);
        Cache::put($cacheKey, $streams, config('twitch.cache.stream_by_id'));

        return $streams;
    }

    /**
     * Retrieves multiple Twitch livestreams by their unique user IDs.
     *
     * @param array $ids
     *
     * @return array
     * @throws TwitchApiException
     * @throws TwitchFormatException
     */
    public function streamsByIds($ids = [])
    {
        if (!is_array($ids)) {
            throw new TwitchFormatException('Array expected, got: ' . gettype($ids));
        }

        return $this->streams(['user_id' => $ids]);
    }

    /**
     * Retrieves a single Twitch livestream by their username
     *
     * @param string $username
     *
     * @return array
     * @throws TwitchApiException
     * @throws TwitchFormatException
     */
    public function streamByName($username = '')
    {
        if (!is_string($username)) {
            $type = gettype($username);
            throw new TwitchFormatException('String expected, got: ' . $type);
        }

        $cacheKey = sprintf('TWITCH_API_STREAM_BY_NAME_%s', $username);

        if (Cache::has($cacheKey)) {
            $cachedStream = Cache::get($cacheKey);
            return $cachedStream;
        }

        $streams = $this->streamsByNames([$username]);
        Cache::put($cacheKey, $streams, config('twitch.cache.stream_by_name'));

        return $streams;
    }

    /**
     * Retrieves multiple Twitch livestreams by their usernames.
     *
     * @param array $usernames
     *
     * @return array
     * @throws TwitchApiException
     * @throws TwitchFormatException
     */
    public function streamsByNames($usernames = [])
    {
        if (!is_array($usernames)) {
            throw new TwitchFormatException('Array expected, got: ' . gettype($usernames));
        }

        return $this->streams(['user_login' => $usernames]);
    }

    /**
     * Sends a request to the `streams` endpoint: https://dev.twitch.tv/docs/api/reference/#get-streams
     *
     * @param array $fields Optional query parameters for `/helix/streams` as documented in the Twitch API documentation.
     *
     * @return array
     * @throws TwitchApiException
     */
    public function streams($fields = [])
    {
        $request = $this->client->get('/streams', $fields);

        if (isset($request['error'])) {
            extract($request);
            throw new TwitchApiException(sprintf('%d: %s - %s', $status, $error, $message), $status);
        }

        $streams = collect($request);
        return Resource\Streams::make($streams)
                               ->resolve();
    }

    /**
     * Retrieve a broadcaster's subscribers, or a specific subscription based on user ID.
     * https://dev.twitch.tv/docs/api/reference/#get-broadcaster-subscriptions
     *
     * `setToken()` should be used prior to requesting subscription information.
     *
     * @param string $broadcasterId User ID for channel/broadcaster
     * @param string $userId User ID for user.
     * @param int $first Amount of subscriptions to retrieve per request. Max 100.
     * @param string $cursor Cursor used for pagination
     *
     * @return array
     * @throws TwitchApiException
     */
    public function subscriptions($broadcasterId = '', $userId = null, $first = 20, $cursor = null)
    {
        $params = [
            'broadcaster_id' => $broadcasterId,
            'first' => $first,
            'user_id' => $userId,
            'after' => $cursor,
        ];

        $request = $this->client->get('/subscriptions', $params);

        if (isset($request['error'])) {
            extract($request);
            throw new TwitchApiException(sprintf('%d: %s - %s', $status, $error, $message), $status);
        }

        $subscriptions = collect($request);

        return Resource\Subscriptions::make($subscriptions)
                                     ->resolve();
    }

    /**
     * Retrieves all the subscribers for a channel.
     *
     * @param string $broadcasterId Channel ID
     *
     * @return array Array of subscriber objects.
     * @throws TwitchApiException
     */
    public function subscriptionsAll($broadcasterId = '')
    {
        $cacheKey = sprintf('TWITCH_SUBSCRIPTIONS_ALL_%s', $broadcasterId);
        if (Cache::has($cacheKey)) {
            $cachedSubscriptions = Cache::get($cacheKey);
            return $cachedSubscriptions;
        }

        $data = $this->subscriptions($broadcasterId, null, 100);
        $subscriptions = $data['subscriptions'];

        $count = $subscriptions->count();
        $subscribers = $subscriptions->resolve();

        while ($count !== 0)
        {
            $cursor = $data['pagination']['cursor'];

            $data = $this->subscriptions($broadcasterId, null, 100, $cursor);
            $subscriptions = $data['subscriptions'];
            $count = $subscriptions->count();

            $subscribers = array_merge($subscribers, $subscriptions->resolve());
        }

        Cache::put($cacheKey, $subscribers, config('twitch.cache.subscriptions_all'));

        return $subscribers;
    }

    /**
     * Retrieves the bare minimum from the Helix subscriptions API,
     * since we're only interested in the subscriber count/points.
     *
     * @param string $broadcasterId Channel ID
     *
     * @return array
     * @throws TwitchApiException
     */
    public function subscriptionsMeta($broadcasterId = '')
    {
        $cacheKey = sprintf('twitch_subscriptions-meta_%s', $broadcasterId);
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $data = $this->subscriptions($broadcasterId);
        Cache::put($cacheKey, $data, config('twitch.cache.subscriptions_meta'));

        return $data;
    }

    /**
     * Requests a specific subscription based on user ID (+ broadcaster ID).
     * https://dev.twitch.tv/docs/api/reference/#get-broadcaster-subscriptions
     *
     * `setToken()` should be used prior to requesting subscription information.
     *
     * @param string $broadcasterId User ID for channel/broadcaster
     * @param string $userId User ID for user.
     *
     * @return array|null
     * @throws TwitchApiException
     */
    public function subscriptionUser($broadcasterId = '', $userId = '')
    {
        $data = $this->subscriptions($broadcasterId, $userId);
        $subscriptions = $data['subscriptions'];

        if (empty($subscriptions))
        {
            return null;
        }

        return $subscriptions[0];
    }

    /**
     * Requests from the Teams API:
     * https://dev.twitch.tv/docs/api/reference#get-teams
     *
     * @param array $fields
     *
     * @return array|null
     * @throws TwitchApiException
     */
    public function teams($fields = [])
    {
        $request = $this->client->get('/teams', $fields);

        if (isset($request['error'])) {
            extract($request);
            throw new TwitchApiException(sprintf('%d: %s - %s', $status, $error, $message), $status);
        }

        $teams = collect($request['data']);
        return Resource\TeamCollection::make($teams)
                                      ->resolve();
    }

    /**
     * Requests a specific team based on team name/slug.
     * https://dev.twitch.tv/docs/api/reference/#get-team
     *
     * @param string $teamName
     *
     * @return array
     * @throws TwitchApiException
     */
    public function teamByName($teamName = '')
    {
        if (!is_string($teamName)) {
            $type = gettype($teamName);
            throw new TwitchFormatException('String expected, got: ' . $type);
        }

        $teams = $this->teams(['name' => $teamName]);
        return $teams[0] ?? [];
    }

    /**
     * Retrieves a list of channels that the specified user is following, as well as the total number of channels.
     *
     * @param string $userId Twitch user ID of the user.
     * @param integer $first Maximum number of objects to return. Maximum: 100. Default: 20.
     * @param string $after Cursor used for pagination.
     *
     * @return array
     * @throws TwitchApiException
     */
    public function userFollows($userId = '', $first = 20, $after = '')
    {
        $params = [
            'from_id' => $userId,
            'first' => $first,
            'after' => $after,
        ];

        $request = $this->client->get('/users/follows', $params);

        if (isset($request['error'])) {
            extract($request);
            throw new TwitchApiException(sprintf('%d: %s - %s', $status, $error, $message), $status);
        }

        $followData = collect($request);

        return Resource\Follow::make($followData)
                              ->resolve();
    }

    /**
     * Requests the user resource: https://dev.twitch.tv/docs/api/reference/#get-users
     *
     * @param array $users
     *
     * @return array
     * @throws TwitchApiException
     */
    public function users($users = [])
    {
        $request = $this->client->get('/users', $users);

        if (isset($request['error'])) {
            extract($request);
            throw new TwitchApiException(sprintf('%d: %s - %s - Users: [%s]', $status, $error, $message, json_encode($users)), $status);
        }

        $users = collect($request['data']);

        return Resource\UserCollection::make($users)
                                      ->resolve();
    }

    /**
     * Request a single user based on their unique Twitch ID.
     *
     * @param string|int $id
     *
     * @return array
     * @throws TwitchApiException
     * @throws TwitchFormatException
     */
    public function userById($id = '')
    {
        if (!is_string($id) && !is_int($id))
        {
            throw new TwitchFormatException('String or int expected, got: ' . gettype($id));
        }

        $ids = [$id];
        $user = $this->usersByIds($ids);

        return $user[0] ?? [];
    }

    /**
     * Request multiple users based on their unique Twitch IDs.
     *
     * @param array $ids
     *
     * @return array
     * @throws TwitchApiException
     * @throws TwitchFormatException
     */
    public function usersByIds($ids = [])
    {
        if (!is_array($ids)) {
            throw new TwitchFormatException('Array expected, got: ' . gettype($ids));
        }

        $users = ['id' => $ids];
        return $this->users($users);
    }

    /**
     * Requests user information based on their login/username.
     * Primarily a wrapper for `usersByUsernames()`.
     *
     * @param string $username
     *
     * @return array
     * @throws TwitchApiException
     * @throws TwitchFormatException
     */
    public function userByUsername($username = '')
    {
        if (!is_string($username)) {
            $type = gettype($username);
            throw new TwitchFormatException('String expected, got: ' . $type);
        }

        $users = $this->usersByUsernames([$username]);
        return $users[0] ?? [];
    }

    /**
     * Requests user information for multiple users based on their login/username.
     * Primarily a wrapper for `users()`.
     *
     * @param array $usernames
     *
     * @return array
     * @throws TwitchApiException
     * @throws TwitchFormatException
     */
    public function usersByUsernames($usernames = [])
    {
        if (!is_array($usernames)) {
            $type = gettype($usernames);

            throw new TwitchFormatException('Array expected, got: ' . $type);
        }

        $users = ['login' => $usernames];
        return $this->users($users);
    }

    /**
     * Similar to `userByUsername()`, but returns a `CachedTwitchUser` model.
     * Will check the cache before querying the Twitch API.
     *
     * @param string $username
     *
     * @return App\CachedTwitchUser
     * @throws TwitchApiException
     */
    public function userByName($username = '')
    {
        if (!is_string($username)) {
            $type = gettype($username);
            throw new TwitchFormatException('String expected, got: ' . $type);
        }

        if (empty($username)) {
            throw new TwitchFormatException('Twitch username cannot be empty.');
        }

        $username = trim(strtolower($username));
        $cachedUser = CachedTwitchUser::where(['username' => $username])->first();

        if (!empty($cachedUser)) {
            return $cachedUser;
        }

        $user = $this->userByUsername($username);

        if (empty($user)) {
            throw new TwitchApiException('User not found: ' . $username);
        }

        $userId = $user['id'];
        $username = $user['login'];

        $checkId = CachedTwitchUser::where(['id' => $userId])->first();
        if (!empty($checkId)) {
            $checkId->username = $username;
            $checkId->save();

            return $checkId;
        }

        $cachedUser = new CachedTwitchUser;
        $cachedUser->id = $userId;
        $cachedUser->username = $username;
        $cachedUser->save();

        return $cachedUser;
    }

    /**
     * Requests the video resource: https://dev.twitch.tv/docs/api/reference#get-videos
     *
     * @param array $params
     *
     * @return array
     * @throws TwitchApiException
     */
    public function videos($params = [])
    {
        $request = $this->client->get('/videos', $params);

        if (isset($request['error'])) {
            extract($request);
            throw new TwitchApiException(sprintf('%d: %s - %s', $status, $error, $message), $status);
        }

        $videos = collect($request['data']);

        return Resource\VideoCollection::make($videos)
                                       ->resolve();
    }
}
