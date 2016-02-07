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

    public function __construct()
    {
        $this->twitchApi = new TwitchApiController(env('TWITCH_CLIENT_ID'), env('TWITCH_CLIENT_SECRET'));
    }

    /**
     * Returns a JSON-array of
     * @param  Request $request
     * @param  string $team Team identifier
     * @return json or string
     */
    public function teamMembers(Request $request, $team = null)
    {
        $teamApi = 'https://api.twitch.tv/api/team/{TEAM_NAME}/all_channels.json';
        if (!empty($team)) {
            $checkTeam = $this->twitchApi->team($team);
            if (!empty($checkTeam['status'])) {
                return response($checkTeam['error'], $checkTeam['status'])->header('Content-Type', 'application/json');
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
     * @return Response
     */
    public function uptime(Request $request)
    {
        $channel = $request->input('channel');
        if (!empty($channel)) {
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
