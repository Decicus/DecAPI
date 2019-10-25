<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Helpers\Helper;

use App\Repositories\BttvApiRepository;
use App\Repositories\TwitchApiRepository;

use Exception;
use App\Exceptions\BttvApiException;
use App\Exceptions\TwitchApiException;

class BttvController extends Controller
{
    /**
     * The base API URL for the BetterTTV API
     *
     * @var string
     */
    private $baseUrl = 'https://api.betterttv.net/2';

    /**
     * @var App\Repositories\BttvApiRepository
     */
    private $bttvApi;

    public function __construct(BttvApiRepository $bttvRepo)
    {
        $this->bttvApi = $bttvRepo;
    }

    /**
     * The BTTV route homepage view
     *
     * @param  Request $request
     * @return Response
     */
    public function home(Request $request)
    {
        $channel = trim($request->input('channel', null));
        $msg = null;
        $data = [
            'channel' => $channel,
            'message' => $msg,
            'page' => 'home'
        ];

        if (empty($channel)) {
            return view('bttv.home', $data);
        }

        try {
            $user = $this->bttvApi->userByTwitchName($channel);
        }
        catch (Exception $ex)
        {
            $data['message'] = 'Unable to retrieve BetterTTV details for channel: ' . $channel;
            return view('bttv.home', $data);
        }

        $emotes = $user['emotes'];
        // Merge channel and 'shared' emotes
        $emotes = array_merge($emotes['channel'], $emotes['shared']);

        $data['user'] = $user;
        $data['emotes'] = $emotes;
        return view('bttv.home', $data);
    }

    /**
     * Retrieves the available BetterTTV channel emotes for the specified channel.
     *
     * @param  Request $request
     * @param  String  $emotes  Route name
     * @param  String  $channel The channel name
     * @return Response
     */
    public function emotes(Request $request, $emotes = null, $channel = null)
    {
        $channel = $channel ?: $request->input('channel', null);
        $types = $request->input('types', 'all');

        if (empty($channel)) {
            return Helper::text('You have to specify a channel name');
        }

        $user = $this->bttvApi->userByTwitchName($twitchId);
        $types = explode(',', $types);

        $emotes = $user['emotes'];

        $channelEmotes = $emotes['channel'];
        $sharedEmotes = $emotes['shared'];

        if (count($channelEmotes) === 0 && count($sharedEmotes) === 0) {
            return Helper::text($channel . ' does not have any BetterTTV emotes.');
        }

        /**
         * Only allow emotes that are 'live', approved and matches the correct type.
         */
        $emoteFilter = function($emote) use ($types) {
            $isLive = $emote['live'];
            // Filter by emote type: `all` is the default value and includes all emote types.
            $isType = in_array('all', $types) || in_array($emote['type'], $types);

            return $isLive && $isType;
        };

        $allEmotes = array_merge($channelEmotes, $sharedEmotes);
        $allEmotes = array_filter($allEmotes, $emoteFilter);

        $getEmoteCodes = function($emote) {
            return $emote['code'];
        };

        $codes = array_map($getEmoteCodes, $allEmotes);
        return Helper::text(implode(' ', $codes));
    }
}
