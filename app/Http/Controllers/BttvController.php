<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Helpers\Helper;

use App\Repositories\BttvApiRepository;
use App\Repositories\TwitchApiRepository;

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

    /**
     * @var App\Repositories\TwitchApiRepository
     */
    private $twitchApi;

    public function __construct(BttvApiRepository $bttvRepo, TwitchApiRepository $twitchRepo)
    {
        $this->bttvApi = $bttvRepo;
        $this->twitchApi = $twitchRepo;
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

        if (!empty($channel)) {
            $emotes = Helper::get($this->baseUrl . '/channels/' . $channel);
            $status = $emotes['status'];

            if ($status === 200) {
                if (count($emotes['emotes']) > 0) {
                    $data['emotes'] = $emotes['emotes'];
                    $data['template'] = str_replace(['{{id}}', '{{image}}'], ['__id__', '__image__'], $emotes['urlTemplate']);
                } else {
                    $msg = 'The channel specified does not have any emotes, but may have some emotes pending approval from the BetterTTV team.';
                }
            } elseif ($status === 404) {
                $msg = 'Channel not found. This usually means the channel has not used the <a href="https://manage.betterttv.net" class="alert-link">BetterTTV management panel</a>.';
            } else {
                $msg = 'Unknown error (the BetterTTV API might be having issues).';
            }
        }

        $data['message'] = $msg;

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

        /**
         * Get Twitch user details based on their username
         * since BetterTTV API v3 uses Twitch IDs instead of names.
         */
        $twitchUser = $this->twitchApi->userByUsername($channel);
        if (empty($twitchUser)) {
            return Helper::text('Invalid Twitch channel name');
        }

        $twitchId = $twitchUser['id'];
        $bttvUser = $this->bttvApi->userByTwitchId($twitchId);
        $types = explode(',', $types);

        $emotes = $bttvUser['emotes'];

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
