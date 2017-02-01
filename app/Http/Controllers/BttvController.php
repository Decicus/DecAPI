<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Helpers\Helper;

class BttvController extends Controller
{
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
            $emotes = Helper::get('https://api.betterttv.net/2/channels/' . $channel);
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

        $emotes = Helper::get('https://api.betterttv.net/2/channels/' . $channel);
        $status = $emotes['status'];
        $types = explode(',', $types);

        if ($status !== 200) {
            return Helper::text($emotes['message']);
        }

        if (count($emotes['emotes']) > 0) {
            $codes = [];
            foreach ($emotes['emotes'] as $emote) {
                if (!in_array('all', $types) && !in_array($emote['imageType'], $types)) {
                    continue;
                }

                $codes[] = $emote['code'];
            }

            return Helper::text(implode(' ', $codes));
        }

        return Helper::text('This channel does not have any emotes, but may have some emotes pending approval from the BetterTTV Team.');
    }
}
