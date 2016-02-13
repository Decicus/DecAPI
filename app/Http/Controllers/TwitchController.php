<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Carbon\Carbon;

class TwitchController extends Controller
{
    private $headers = [
        'Content-Type' => 'text/plain',
        'Access-Control-Allow-Origin' => '*'
    ];
    private $twitchApi;

    /**
     * Initiliazes the controller with a reference to TwitchApiController.
     */
    public function __construct()
    {
        $this->twitchApi = new TwitchApiController(env('TWITCH_CLIENT_ID'), env('TWITCH_CLIENT_SECRET'));
    }

    /**
     * Returns an error response
     * @param  string  $message Error message
     * @param  integer $code    HTTP error code, default: 404
     * @return Response
     */
    protected function error($message, $code = 404)
    {
        return response($message, $code)->withHeaders($this->headers);
    }

    /**
     * Returns an error JSON response
     * @param  Request $request
     * @param  array  $data
     * @param  integer $code
     * @return Response
     */
    protected function errorJson(Request $request, $data = [], $code = 404)
    {
        $data['code'] = $code;
        return response()->json($data)->setCallback($request->input('callback'))->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * Returns the latest highlight of channel.
     * @param  Request $request
     * @param  string  $channel Channel name
     * @return Response           Latest highlight and title of highlight.
     */
    public function highlight(Request $request, $channel = null)
    {
        $channel = $channel ?: $request->input('channel', null);

        if (empty($channel)) {
            return $this->error('You have to specify a channel');
        }

        $fetchHighlight = $this->twitchApi->videos($request, $channel);

        if (!empty($fetchHighlight['status'])) {
            return $this->error($fetchHighlight['message'], $fetchHighlight['status']);
        }

        if (empty($fetchHighlight['videos'])) {
            return $this->error($channel . ' has no saved highlights', 200);
        }

        $highlight = $fetchHighlight['videos'][0];
        $title = $highlight['title'];
        $url = $highlight['url'];
        return response($title . " - " . $url)->withHeaders($this->headers);
    }

    /**
     * Return list of hosts for a channel
     * @param  Request $request
     * @param  string  $channel Channel name
     * @return Response
     */
    public function hosts(Request $request, $channel = null)
    {
        $channel = $channel ?: $request->input('channel', null);
        if (empty($channel)) {
            $message = 'Channel cannot be empty';
            if($request->wantsJson()) {
                return $this->errorJson($request, ['message' => $message]);
            }
            return $this->error($message);
        }

        $hosts = $this->twitchApi->hosts($channel);
        if (!empty($hosts['status'])) {
            $message = $hosts['message'];
            $code = $hosts['status'];
            if ($request->wantsJson()) {
                return $this->errorJson($request, ['message' => $message], $code);
            }
            return $this->error($message, $code);
        }

        if (empty($hosts)) {
            $message = 'No one is currently hosting ' . $channel;
            if ($request->wantsJson()) {
                return $this->errorJson($request, ['message' => $message]);
            }
            return $this->error($message);
        }

        $hostList = [];
        foreach($hosts as $host) {
            $hostList[] = $host['host_login'];
        }

        if ($request->wantsJson()) {
            return response()->json($hostList)->setCallback($request->input('callback'))->header('Access-Control-Allow-Origin', '*');
        }

        $implode = $request->exists('implode') ? ', ' : PHP_EOL;
        return response(implode($implode, $hostList))->withHeaders($this->headers);
    }

    /**
     * Returns a list of team members
     * @param  Request $request
     * @param  string $team Team identifier
     * @return Response
     */
    public function teamMembers(Request $request, $team = null)
    {
        $teamApi = 'https://api.twitch.tv/api/team/{TEAM_NAME}/all_channels.json';

        $team = $team ?: $request->input('team', null);
        if (empty($team)) {
            $message = 'Team identifier is empty';
            if ($request->wantsJson()) {
                return $this->errorJson($request, ['message' => $message]);
            }
            return $this->error($message);
        }

        $checkTeam = $this->twitchApi->team($team);
        if (!empty($checkTeam['status'])) {
            $message = $checkTeam['message'];
            $code = $checkTeam['status'];
            if($request->wantsJson()) {
                return $this->errorJson($request, ['message' => $message], $code);
            }
            return $this->error($message, $code);
        }

        $data = $this->twitchApi->get(str_replace('{TEAM_NAME}', $team, $teamApi), true);
        $inputs = $request->all();
        $members = [];
        foreach ($data['channels'] as $member) {
            $members[] = $member['channel']['name'];
        }

        if (isset($inputs['sort'])) {
            sort($members);
        }

        if ($request->wantsJson()) {
            return response()->json($members)->setCallback($request->input('callback'))->header('Access-Control-Allow-Origin', '*');
        }
        return response(implode("\r\n", $members))->withHeaders($this->headers);
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
        $channel = $channel ?: $request->input('channel', null);
        if (empty($channel)) {
            return $this->error('Channel cannot be empty');
        }

        $stream = $this->twitchApi->streams($channel);
        if (!empty($stream['status'])) {
            return $this->error($stream['message'], $stream['status']);
        }

        if (empty($stream['stream'])) {
            return $this->error($channel . ' is offline');
        }

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
    }
}
