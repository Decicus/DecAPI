<?php

namespace App\Repositories;

use App\Services\TwitchApiClient;

use App\Exceptions\TwitchApiException;
use App\Exceptions\TwitchFormatException;

use App\Http\Resources\Twitch as Resource;

class TwitchApiRepository
{
    /**
     * @var App\Services\TwitchApiClient
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
     * Retrieves the channel's followers, as well as the total number of followers.
     *
     * @param string $toId Twitch user ID of the channel
     * @param integer $first Maximum number of objects to return. Maximum: 100. Default: 20.
     * @param string $after Cursor used for pagination.
     *
     * @return App\Http\Resources\Twitch\Follow
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
            throw new TwitchApiException(sprintf('%d: %s - %s', $status, $error, $message));
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
     * @return App\Http\Resources\Twitch\FollowUserCollection
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
            throw new TwitchApiException(sprintf('%d: %s - %s', $status, $error, $message));
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
     * @return App\Http\Resources\Twitch\Streams
     */
    public function streamById($id = '')
    {
        if (!is_string($id) && !is_int($id))
        {
            throw new TwitchFormatException('String or int expected, got: ' . gettype($id));
        }

        return $this->streamsByIds([$id]);
    }

    /**
     * Retrieves multiple Twitch livestreams by their unique user IDs.
     *
     * @param array $ids
     *
     * @return App\Http\Resources\Twitch\Streams
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
     * @return App\Http\Resources\Twitch\Streams
     */
    public function streamByName($username = '')
    {
        if (!is_string($username)) {
            $type = gettype($username);
            throw new TwitchFormatException('String expected, got: ' . $type);
        }

        return $this->streamsByNames([$username]);
    }

    /**
     * Retrieves multiple Twitch livestreams by their usernames.
     *
     * @param array $usernames
     *
     * @return App\Http\Resources\Twitch\Streams
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
     * @return App\Http\Resources\Twitch\Streams
     */
    public function streams($fields = [])
    {
        $request = $this->client->get('/streams', $fields);

        if (isset($request['error'])) {
            extract($request);
            throw new TwitchApiException(sprintf('%d: %s - %s', $status, $error, $message));
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
     * @param int    $first  Amount of subscriptions to retrieve per request. Max 100.
     * @param string $cursor Cursor used for pagination
     *
     * @return array
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
            throw new TwitchApiException(sprintf('%d: %s - %s', $status, $error, $message));
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
     */
    public function subscriptionsAll($broadcasterId = '')
    {
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

        return $subscribers;
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
     * @return App\Http\Resources\Twitch\Subscriptions
     */
    public function subscriptionUser($broadcasterId = '', $userId = '')
    {
        $data = $this->subscriptions($broadcasterId, $userId);
    }

    /**
     * Requests the user resource: https://dev.twitch.tv/docs/api/reference/#get-users
     *
     * @param array $users
     *
     * @return array
     */
    public function users($users = [])
    {
        $request = $this->client->get('/users', $users);

        if (isset($request['error'])) {
            extract($request);
            throw new TwitchApiException(sprintf('%d: %s - %s - Users: [%s]', $status, $error, $message, json_encode($users)));
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
}
