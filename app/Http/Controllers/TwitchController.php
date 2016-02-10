<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Carbon\Carbon;

class TwitchController extends Controller
{
    private $headers = ['Content-Type' => 'text/plain'];
    private $twitchApi;

    /**
     * Initiliazes the controller with a reference to TwitchApiController.
     */
    public function __construct()
    {
        $this->twitchApi = new TwitchApiController(env('TWITCH_CLIENT_ID'), env('TWITCH_CLIENT_SECRET'));
    }

    /**
     * Returns the latest highlight of channel.
     * @param  Request $request
     * @param  string  $channel Channel name
     * @return Response           Latest highlight and title of highlight.
     */
    public function highlight(Request $request, $channel = null)
    {
        if (!empty($channel) || $request->has('channel')) {
            if (empty($channel)) {
                $channel = $request->input('channel');
            }

            $fetchHighlight = $this->highlights($request, $channel);
            if (!empty($fetchHighlight['status'])) {
                return response($fetchHighlight['error'], $fetchHighlight['status'])->withHeaders($this->headers);
            } else {
                if (empty($fetchHighlight['videos'])) {
                    return response($channel . ' has no saved highlights')->withHeaders($this->headers);
                } else {
                    $highlight = $fetchHighlight['videos'][0];
                    $title = $highlight['title'];
                    $url = $highlight['url'];
                    return response($title . " - " . $url)->withHeaders($this->headers);
                }
            }
        } else {
            return response('You have to specify a channel', 404)->withHeaders($this->headers);
        }
    }

    /**
     * Returns result of the Kraken API for highlights.
     * @param  Request $request
     * @param  [type]  $channel Channel name, can also be specified in the request.
     * @param  integer $limit   Limit of highlights
     * @param  integer $offset  Offset
     * @return array            JSON-decoded result of highlights endpoint           
     */
    public function highlights(Request $request, $channel, $limit = 1, $offset = 0)
    {
        $input = $request->all();
        if (!empty($channel) || $request->has('channel')) {
            if (empty($channel)) {
                $channel = $input['channel'];
            }

            $limit = ($request->has('limit') && intval($input['limit']) ? intval($input['limit']) : $limit);
            $offset = ($request->has('offset') ? intval($input['offset']) : $offset);
            return $this->twitchApi->channels($channel . '/videos?limit=' . $limit . '&offset=' . $offset);
        } else {
            throw new Exception('You have to specify channel');
        }
    }

    /**
     * Returns a JSON-array of
     * @param  Request $request
     * @param  string $team Team identifier
     * @return Response
     */
    public function teamMembers(Request $request, $team = null)
    {
        $teamApi = 'https://api.twitch.tv/api/team/{TEAM_NAME}/all_channels.json';
        if (!empty($team) || !$request->has('team')) {
            if (empty($team)) {
                $team = $request->input('team');
            }
            $checkTeam = $this->twitchApi->team($team);
            if (!empty($checkTeam['status'])) {
                return response($checkTeam['error'], $checkTeam['status'])->withHeaders($this->headers);
            } else {
                $data = $this->twitchApi->get(str_replace('{TEAM_NAME}', $team, $teamApi), true);
                $inputs = $request->all();
                $members = [];
                foreach ($data['channels'] as $member) {
                    $members[] = $member['channel']['name'];
                }

                if (isset($inputs['sort'])) {
                    sort($members);
                }

                if (!$request->wantsJson()) {
                    return response(implode(PHP_EOL, $members))->withHeaders($this->headers);
                } else {
                    return response()->json($members)->setCallback($request->input('callback'));
                }
            }
        } else {
            return response('Team identifier is empty', 404)->withHeaders($this->headers);
        }
    }

    /**
     * Checks the uptime of the current stream.
     *
     * @param Request $request
     * @param string $channel Channel name
     * @return Response
     */
    public function uptime(Request $request, $channel = null)
    {
        if (!empty($channel) || $request->has('channel')) {
            if (empty($channel)) {
                $channel = $request->input('channel');
            }

            $stream = $this->twitchApi->streams($channel);
            if (!empty($stream['status'])) {
                return response($stream['error'], $stream['status'])->withHeaders($this->headers);
            } elseif ($stream['stream']) {
                $date = Carbon::parse($stream['stream']['created_at']);
                $uptime = [];
                $days = $date->diffInDays();
                $hours = ($date->diffInHours() - 24 * $days);
                $minutes = ($date->diffInMinutes() - (60 * $hours) - (24 * $days * 60));
                $seconds = ($date->diffInSeconds() - (60 * $minutes) - (60 * $hours * 60) - (24 * $days * 60 * 60));
                if ($days > 0) {
                    $uptime[] = $days . " day" . ($days > 1 ? 's' : '');
                }

                if ($hours > 0) {
                    $uptime[] = $hours . " hour" . ($hours > 1 ? 's' : '');
                }

                if ($minutes > 0) {
                    $uptime[] = $minutes . " minute" . ($minutes > 1 ? 's' : '');
                }

                if ($seconds > 0) {
                    $uptime[] = $seconds . " second" . ($seconds > 1 ? 's' : '');
                }

                return response(implode(', ', $uptime))->withHeaders($this->headers);
            } else {
                return response('Channel is offline')->withHeaders($this->headers);
            }
        } else {
            return response('Channel cannot be empty', 404)->withHeaders($this->headers);
        }
    }
}
