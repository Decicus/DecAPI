<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Repositories\TwitchApiRepository;

use App\Helpers\Helper;
use Exception;

class FfzController extends Controller
{
    /**
     * The base API URL for the FrankerFaceZ API.
     */
    const FFZ_BASE_URL = 'https://api.frankerfacez.com/v1';

    /**
     * Generic error messages
     */
    const CHANNEL_NOT_SPECIFIED = 'A channel name has to be specified.';
    const UNRESOLVED_TWITCH_ID = 'An error occurred translating the Twitch username to a valid user ID.';

    /**
     * @var TwitchApiRepository
     */
    private $twitchApi;

    public function __construct(TwitchApiRepository $apiRepository)
    {
        $this->twitchApi = $apiRepository;
    }

    /**
     * Retrieves a list of FFZ emotes in the specified channel.
     *
     * @param  Request $request
     * @param  string  $name
     * @return Response
     */
    public function emotes(Request $request, $channel = null)
    {
        if (empty($channel)) {
            return Helper::text(self::CHANNEL_NOT_SPECIFIED);
        }

        try {
            $user = $this->twitchApi->userByName($channel);
            $channelId = $user->id;
        } catch (Exception $e) {
            return Helper::text(self::UNRESOLVED_TWITCH_ID);
        }

        $separator = $request->input('separator', ' ');

        $url = sprintf('%s/room/id/%s', self::FFZ_BASE_URL, $channelId);
        $data = Helper::get($url);

        if (empty($data['error']) === false) {
            $error = sprintf('%s - %s', $data['error'], $data['message']);
            return Helper::text($error);
        }

        $sets = $data['sets'];
        $emoteNames = [];
        foreach ($sets as $set) {
            foreach ($set['emoticons'] as $emote) {
                $emoteNames[] = $emote['name'];
            }
        }

        return Helper::text(implode($separator, $emoteNames));
    }
}
