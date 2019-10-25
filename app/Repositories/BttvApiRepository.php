<?php

namespace App\Repositories;

use App\Services\BttvApiClient;

use App\Http\Resources\Bttv as Resource;

use Exception;
use App\Exceptions\BttvApiException;
class BttvApiRepository
{
    /**
     * @var App\Services\BttvApiClient
     */
    private $client;

    /**
     * @var App\Repositories\TwitchApiRepository
     */
    private $twitchApi;

    public function __construct(BttvApiClient $client, TwitchApiRepository $twitchRepo)
    {
        $this->client = $client;
        $this->twitchApi = $twitchRepo;
    }

    /**
     * Retrieves user information based on their BetterTTV user ID.
     *
     * @param string $userId
     *
     * @return App\Http\Resources\Bttv\User
     */
    public function userById($userId = '')
    {
        $request = $this->client->get('/users/' . $userId);

        if (isset($request['message'])) {
            throw new BttvApiException(sprintf('Error occurred retrieving information for user ID: %s - %s', $userId, $request['message']));
        }

        $userData = collect($request);

        return Resource\User::make($userData)
                            ->resolve();
    }

    /**
     * Retrieves user information based on their Twitch user ID.
     *
     * @param string $twitchId
     *
     * @return App\Http\Resources\Bttv\User
     */
    public function userByTwitchId($twitchId = '')
    {
        $request = $this->client->get('/cached/users/twitch/' . $twitchId);

        if (isset($request['message'])) {
            throw new BttvApiException(sprintf('Error occurred retrieving information for user ID: %s - %s', $twitchId, $request['message']));
        }

        $userId = $request['id'];

        return $this->userById($userId);
    }

    /**
     * Retrieves user information based on their Twitch username.
     * Twitch user ID is retrieved from the Twitch API before being passed to userByTwitchId().
     *
     * @param string $username
     *
     * @return App\Http\Resources\Bttv\User
     */
    public function userByTwitchName($username = '')
    {
        try {
            $twitchUser = $this->twitchApi->userByUsername($username);
        }
        catch (Exception $ex)
        {
            throw new BttvApiException('Unable to retrieve Twitch details for: ' . $username);
        }

        if (empty($twitchUser)) {
            throw new BttvApiException('Invalid Twitch channel name');
        }

        $twitchId = $twitchUser['id'];
        return $this->userByTwitchId($twitchId);
    }
}
