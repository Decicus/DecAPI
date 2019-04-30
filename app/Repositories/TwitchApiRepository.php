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
     * Requests the user resource: https://dev.twitch.tv/docs/api/reference/#get-users
     *
     * @param array $users
     *
     * @return array
     */
    public function users($users = [])
    {
        $request = $this->client->get('/users', $users);
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
