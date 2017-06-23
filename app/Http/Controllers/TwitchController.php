<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\User;
use Auth;
use App\Helpers\Helper;
use App\Helpers\Nightbot;
use App\TwitchHelpArticle as HelpArticle;

use Carbon\Carbon;
use DateTimeZone;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\CssSelector;
use GuzzleHttp\Client;

use Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

use Exception;

class TwitchController extends Controller
{
    /**
     * @var array
     */
    private $headers = [
        'Content-Type' => 'text/plain',
        'Access-Control-Allow-Origin' => '*'
    ];

    /**
     * @var TwitchApiController
     */
    private $twitchApi;

    /**
     * The 'Accept' header to receive Twitch API V5 responses.
     *
     * @var array
     */
    private $version = ['Accept' => 'application/vnd.twitchtv.v5+json'];

    /**
     * Initiliazes the controller with a reference to TwitchApiController.
     */
    public function __construct()
    {
        $this->twitchApi = new TwitchApiController(env('TWITCH_CLIENT_ID'), env('TWITCH_CLIENT_SECRET'));
    }

    /**
     * Returns an error response
     *
     * @param  string  $message Error message
     * @param  integer $code    HTTP error code, default: 404
     * @return Response
     */
    protected function error($message, $code = 404)
    {
        return Helper::text($message, $code, $this->headers);
    }

    /**
     * Returns an error JSON response
     *
     * @param  array  $data
     * @param  integer $code
     * @return Response
     */
    protected function errorJson($data = [], $code = 404)
    {
        return Helper::json($data, $code);
    }

    /**
     * Returns a JSON response with set headers
     * @param  array  $data
     * @param  integer $code    HTTP status code
     * @param  array  $headers HTTP headers
     * @return response
     */
    protected function json($data = [], $code = 200, $headers = [])
    {
        $headers['Content-Type'] = 'application/json';
        $headers['Access-Control-Allow-Origin'] = '*';

        return \Response::json($data, $code)->withHeaders($headers);
    }

    /**
     * Retrieves the Twitch user object specified by login name.
     * Throws an exception on errors.
     *
     * @param  string $name The username to look for.
     * @return array
     */
    protected function userByName($name)
    {
        try {
            return $this->twitchApi->userByName($name);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * The base API request
     *
     * @return response
     */
    public function base()
    {
        $baseUrl = url('/twitch/');

        $urls = [
            'chat_rules' => 'chat_rules/{CHANNEL}',
            'clusters' => 'clusters/{CHANNEL}',
            'emoteslots' => 'emoteslots/{CHANNEL}',
            'followage' => 'followage/{CHANNEL}/{USER}',
            'followed' => 'followed/{USER}/{CHANNEL}',
            'game' => 'game/{CHANNEL}',
            'help' => 'help/{SEARCH}',
            'highlight' => 'highlight/{CHANNEL}',
            'hosts' => 'hosts/{CHANNEL}',
            'id' => 'id/{USER}',
            'ingests' => 'ingests',
            'multi' => 'multi/{STREAMS}',
            'subcount' => 'subcount/{CHANNEL}',
            'subscriber_emotes' => 'subscriber_emotes/{CHANNEL}',
            'status' => 'status/{CHANNEL}',
            'title' => 'title/{CHANNEL}',
            'team_members' => 'team_members/{TEAM_ID}',
            'upload' => 'upload/{CHANNEL}',
            'uptime' => 'uptime/{CHANNEL}'
        ];

        foreach ($urls as $name => $endpoint) {
            $urls[$name] = $baseUrl . '/' . $endpoint;
        }

        return $this->json([
            'endpoints' => $urls
        ]);
    }

    /**
     * Retrieves the account age of the specified user.
     *
     * @param  Request $request
     * @param  string  $user
     * @return Response
     */
    public function accountAge(Request $request, $user = null)
    {
        $id = $request->input('id', false);
        $precision = intval($request->input('precision', 2));

        if (empty($user)) {
            $nb = new Nightbot($request);
            if (empty($nb->user)) {
                return Helper::text('You need to specify a username!');
            }

            $user = $user ?: $nb->user['providerId'];
            $id = 'true';
        }

        if ($id !== 'true') {
            try {
                $user = $this->userByName($user)->id;
            } catch (Exception $e) {
                return Helper::text($e->getMessage());
            }
        }

        $userData = $this->twitchApi->users($user, $this->version);

        if (!empty($userData['status'])) {
            return Helper::text($userData['message']);
        }

        $time = $userData['created_at'];
        $time = Helper::getDateDiff($time, time(), $precision);

        return Helper::text($time);
    }

    /**
     * Returns a text list with chat rules of the specified channel.
     *
     * @param  Request $request
     * @param  string  $channel Channel name
     * @return Response
     */
    public function chatRules(Request $request, $channel = null)
    {
        $channel = $channel ?: $request->input('channel', null);

        if (empty($channel)) {
            return Helper::text('Channel name has to be specified.');
        }

        $url = sprintf('https://api.twitch.tv/api/channels/%s/chat_properties', $channel);
        $data = $this->twitchApi->get($url, true);

        if (isset($data['error'])) {
            return Helper::text($data['message']);
        }

        $rules = $data['chat_rules'];

        if (empty($rules)) {
            return Helper::text($channel . ' does not have any rules set.');
        }

        $rules = implode(PHP_EOL, $rules);
        return Helper::text($rules);
    }

    /**
     * Returns the Twitch chat cluster for the specified channel.
     * Added purely for backwards compatibility as it's not necessary as of March 23rd 2016.
     *
     * @param  Request $request
     * @param  string  $channel Channel name
     * @return Response
     */
    public function clusters(Request $request, $channel = null)
    {
        $channel = $channel ?: $request->input('channel', null);

        if (empty($channel)) {
            return $this->error('A channel has to be specified');
        }

        $cluster = Helper::get('https://tmi.twitch.tv/servers?channel=' . $channel, [
            'Client-ID' => env('TWITCH_CLIENT_ID')
        ]);

        if (empty($cluster)) {
            return $this->error('An error occurred retrieving cluster.');
        }

        return Helper::text($cluster['cluster']);
    }

    /**
     * Retrieve the creation time and date of the specified user.
     *
     * @param  Request $request
     * @param  string  $route
     * @param  string  $user
     * @return Response
     */
    public function creation(Request $request, $route = null, $user = null)
    {
        $user = $user ?: ($request->input('name', null) ?: $request->input('user', null));
        $id = $request->input('id', 'false');
        $tz = $request->input('tz', 'UTC');
        $format = $request->input('format', 'M j. Y - h:i:s A (e)');
        // https://secure.php.net/manual/en/timezones.php
        $allTimezones = DateTimeZone::listIdentifiers();

        if (!in_array($tz, $allTimezones)) {
            return Helper::text('Invalid timezone specified: ' . $tz);
        }

        if (empty($user)) {
            $nb = new Nightbot($request);
            if (empty($nb->user)) {
                return Helper::text('You need to specify a username!');
            }

            $user = $user ?: $nb->user['providerId'];
            $id = 'true';
        }

        if ($id !== 'true') {
            try {
                $user = $this->userByName($user)->id;
            } catch (Exception $e) {
                return Helper::text($e->getMessage());
            }
        }

        $userData = $this->twitchApi->users($user, $this->version);

        if (!empty($userData['status'])) {
            return Helper::text($userData['message']);
        }

        $time = $userData['created_at'];
        $time = Carbon::parse($userData['created_at']);
        $time->setTimezone($tz);

        return Helper::text($time->format($format));
    }

    /**
     * Uses the specified subscriber count to see how many subscribers are needed to open a certain amount of emoteslots.
     *
     * @param  Request $request
     * @param  string  $channel The channel name
     * @return Response
     */
    public function emoteslots(Request $request, $channel = null)
    {
        $nb = new Nightbot($request);
        $subs = $request->input('subscribers', null) ?: $request->input('subs', null);

        if (empty($channel)) {
            if (empty($nb->channel)) {
                return Helper::text('A channel name has to be specified.');
            }

            $channel = $nb->channel['displayName'];
        }

        if (empty($subs)) {
            return Helper::text('A subscriber ("subscribers") count has to be specified.');
        }

        $format = urldecode($request->input('format', '{1} currently has {2} subscribers and is {3} subscriber(s) away from {4} emote slots!'));
        $subs = intval($subs);
        // config/twitch.php
        $slotMap = config('twitch.emoteslots');

        $count = null;
        foreach ($slotMap as $subcount => $slots) {
            if ($subs < $subcount) {
                $count = $subcount;
                break;
            }
        }

        if (!empty($count)) {
            $diff = $count - $subs;
            $result = str_replace(['{1}', '{2}', '{3}', '{4}'], [$channel, $subs, $diff, $slotMap[$count]], $format);
        } else {
            $max = end($slotMap);
            reset($slotMap);
            $result = sprintf('%s has the maximum emote slots (%d) with %d subscribers!', $channel, $max, $subs);
        }

        return Helper::text($result);
    }

    /**
     * Returns the length a user has followed a channel
     *
     * @param  Request $request
     * @param  string  $channel
     * @param  string  $user
     * @return Response
     */
    public function followAge(Request $request, $channel = null, $user = null)
    {
        $channel = $channel ?: $request->input('channel', null);
        $user = $user ?: $request->input('user', null);
        $id = $request->input('id', 'false');

        $precision = intval($request->input('precision')) ? intval($request->input('precision')) : 2;

        if (empty($channel) || empty($user)) {
            $nb = new Nightbot($request);
            if (empty($nb->channel) || empty($nb->user)) {
                return Helper::text('You need to specify both user and channel name');
            }

            $channel = $channel ?: $nb->channel['providerId'];
            $user = $user ?: $nb->user['providerId'];
            $id = 'true';
        }

        $channel = trim($channel);
        $user = trim($user);

        if (strtolower($channel) === strtolower($user)) {
            return Helper::text('A user cannot follow themself.');
        }

        if ($id !== 'true') {
            try {
                $channel = $this->userByName($channel)->id;
                $user = $this->userByName($user)->id;
            } catch (Exception $e) {
                return Helper::text($e->getMessage());
            }
        }

        $getFollow = $this->twitchApi->followRelationship($user, $channel, $this->version);

        if (!empty($getFollow['status'])) {
            return response($getFollow['message'])->withHeaders($this->headers);
        }

        $time = $getFollow['created_at'];
        $diff = Helper::getDateDiff($time, time(), $precision);
        return response($diff)->withHeaders($this->headers);
    }

    /**
     * Retrieves the specified channel's follower count.
     *
     * @param  Request $request
     * @param  string  $channel
     * @return Response
     */
    public function followCount(Request $request, $channel = null)
    {
        $id = $request->input('id', 'false');

        if (empty($channel)) {
            $nb = new Nightbot($request);
            if (empty($nb->channel)) {
                return Helper::text('A channel has to be specified.');
            }

            $channel = $nb->channel['providerId'];
            $id = 'true';
        }

        if ($id !== 'true') {
            try {
                $channel = $this->userByName($channel)->id;
            } catch (Exception $e) {
                return Helper::text($e->getMessage());
            }
        }

        $getFollowers = $this->twitchApi->channelFollows($channel, 1, 0, 'desc', $this->version);

        if (!empty($getFollowers['status'])) {
            return Helper::text($getFollowers['message']);
        }

        return Helper::text($getFollowers['_total']);
    }

    /**
     * Shows the date and time of when a user followed a channel.
     *
     * @param  Request $request
     * @param  string  $followed
     * @param  string  $channel
     * @param  string  $user
     * @return Response
     */
    public function followed(Request $request, $followed = null, $channel = null, $user = null)
    {
        $user = $user ?: $request->input('user', null);
        $channel = $channel ?: $request->input('channel', null);
        $id = $request->input('id', 'false');
        $tz = $request->input('tz', 'UTC');
        $format = $request->input('format', 'M j. Y - h:i:s A (e)');
        // https://secure.php.net/manual/en/timezones.php
        $allTimezones = DateTimeZone::listIdentifiers();

        if (empty($user) || empty($channel)) {
            $nb = new Nightbot($request);
            if (empty($nb->channel) || empty($nb->user)) {
                return Helper::text('You need to specify both user and channel name');
            }

            $channel = $channel ?: $nb->channel['providerId'];
            $user = $user ?: $nb->user['providerId'];
            $id = 'true';
        }

        if (!in_array($tz, $allTimezones)) {
            return Helper::text('Invalid timezone specified: ' . $tz);
        }

        if (strtolower($user) === strtolower($channel)) {
            return Helper::text('A user cannot follow themself.');
        }

        if ($id !== 'true') {
            try {
                $channel = $this->userByName($channel)->id;
                $user = $this->userByName($user)->id;
            } catch (Exception $e) {
                return Helper::text($e->getMessage());
            }
        }

        $getFollow = $this->twitchApi->followRelationship($user, $channel, $this->version);

        // If $user isn't following $channel, a 404 is returned.
        if (!empty($getFollow['status'])) {
            return Helper::text($getFollow['message']);
        }

        $time = Carbon::parse($getFollow['created_at']);
        $time->setTimezone($tz);

        return Helper::text($time->format($format));
    }

    /**
     * Retrieves and lists the latest followers for a channel.
     *
     * @param  Request $request
     * @param  string  $route
     * @param  string  $channel
     * @return Response
     */
    public function followers(Request $request, $route, $channel = null)
    {
        $channel = $channel ?: $request->input('channel', null);
        $count = intval($request->input('count', 1));
        $offset = intval($request->input('offset', 0));
        $direction = $request->input('direction', 'desc');
        $showNumbers = ($request->exists('num') || $request->exists('show_num')) ? true : false;
        $separator = $request->input('separator', ', ');

        $id = $request->input('id', 'false');

        if (empty($channel)) {
            $nb = new Nightbot($request);
            if (empty($nb->channel)) {
                return Helper::text('A channel has to be specified.');
            }

            $channel = $nb->channel['providerId'];
            $id = 'true';
        }

        if ($id !== 'true') {
            try {
                $channel = $this->userByName($channel)->id;
            } catch (Exception $e) {
                return Helper::text($e->getMessage());
            }
        }

        if ($count > 100) {
            return Helper::text('Count cannot be more than 100.');
        }

        $followers = $this->twitchApi->channelFollows($channel, $count, $offset, $direction, $this->version);

        if (!empty($followers['status'])) {
            return Helper::text($followers['message']);
        }

        $users = [];
        $currentNumber = 0;
        foreach ($followers['follows'] as $user) {
            $currentNumber++;
            $users[] = ($showNumbers ? $currentNumber . '. ' : '') . $user['user']['display_name'];
        }

        return Helper::text(implode($separator, $users));
    }

    /**
     * Gets the game of the specified channel
     *
     * @param  Request $request
     * @param  string  $route   Route: game/status/title
     * @param  string  $channel Channel name
     * @return Response
     */
    public function gameOrStatus(Request $request, $route, $channel = null)
    {
        $id = $request->input('id', 'false');
        if ($route !== 'game') {
            $route = 'status';
        }

        $channel = $channel ?: $request->input('channel', null);

        if (empty($channel)) {
            $nb = new Nightbot($request);
            if (empty($nb->channel)) {
                return Helper::text('Channel name has to be specified.');
            }

            $channel = $nb->channel['providerId'];
            $id = 'true';
        }

        if ($id !== 'true') {
            try {
                $channel = $this->userByName($channel)->id;
            } catch (Exception $e) {
                return Helper::text($e->getMessage());
            }
        }

        $getGame = $this->twitchApi->channels($channel, $this->version);

        if (!empty($getGame['message'])) {
            return Helper::text($getGame['message']);
        }

        $text = $getGame[$route];
        return Helper::text($text ?: 'Not set');
    }

    /**
     * Attempts to find a help page that is related to the search query.
     *
     * @param  Request $request
     * @param  string  $search  Search query
     * @return Response
     */
    public function help(Request $request, $search = null)
    {
        $lang = $request->input('lang', 'en');
        $search = strtolower(trim($search));

        $prefix = 'https://help.twitch.tv/customer/' . $lang . '/portal/articles/';

        $json = $request->wantsJson();
        if ($request->exists('list') || ($json && $search === 'list')) {
            $articles = [];
            $helpArticles = HelpArticle::select('id', 'title')
                            ->get()
                            ->sortBy('title');

            foreach ($helpArticles as $article) {
                $articles[$article->title] = $article->id;
            }

            $data = [
                'url_template' => $prefix . '{id}',
                'articles' => $articles
            ];

            if ($json) {
                return $this->json($data);
            }

            $data = [
                'list' => $data['articles'],
                'page' => 'Help Articles',
                'prefix' => $prefix
            ];
            return view('shared.list', $data);
        }

        $msg = null;
        $code = null;

        if (empty($search) || $search === 'list') {
            return Helper::text('List of available help articles with titles: ' . route('twitch.help') . '?list');
        }

        $articles = HelpArticle::search($search)
                    ->select('id', 'title', 'published')
                    ->latest('published')
                    ->orderBy('title')
                    ->get();

        if (empty($articles)) {
            $msg = 'No results found.';
            $code = 404;
        }

        if ($code !== null) {
            if ($json) {
                $data = [
                    'error' => $msg,
                    'code' => $code
                ];
                return $this->errorJson($data, $code);
            }

            // Send with code 200, so Nightbot picks up the returned message
            return Helper::text($msg);
        }

        $article = $articles->first();
        $title = $article->title;
        $url = $prefix . $article->id;
        if ($json) {
            $data = [
                'code' => 200,
                'title' => $title,
                'url' => $url,
                'results' => $articles->toArray()
            ];
            return Helper::json($data);
        }

        return Helper::text($title . " - " . $url);
    }

    /**
     * Returns the latest highlight of channel.
     *
     * @param  Request $request
     * @param  string  $highlight
     * @param  string  $channel Channel name
     * @return Response           Latest highlight and title of highlight.
     */
    public function highlight(Request $request, $highlight = null, $channel = null)
    {
        $channel = $channel ?: $request->input('channel', null);
        $channelName = null;
        $id = $request->input('id', 'false');

        if (empty($channel)) {
            $nb = new Nightbot($request);
            if (empty($nb->channel)) {
                return Helper::text('You have to specify a channel');
            }

            $channel = $nb->channel['providerId'];
            $id = 'true';
        }

        if ($id !== 'true') {
            try {
                // Store channel name separately and override $channel
                $channelName = $channel;
                $channel = $this->userByName($channel)->id;
            } catch (Exception $e) {
                return Helper::text($e->getMessage());
            }
        }

        $fetchHighlight = $this->twitchApi->videos($request, $channel, ['highlight'], 1, 0, $this->version);

        if (!empty($fetchHighlight['status'])) {
            return Helper::text($fetchHighlight['message']);
        }

        if (empty($fetchHighlight['videos'])) {
            return Helper::text(($channelName ?: $channel) . ' has no saved highlights');
        }

        $highlight = $fetchHighlight['videos'][0];
        $title = $highlight['title'];
        $url = $highlight['url'];
        return response($title . " - " . $url)->withHeaders($this->headers);
    }

    /**
     * Retrieves a random highlight from the channel.
     *
     * @param  Request $request
     * @param  String  $highlight_random Route name
     * @param  String  $channel          The channel name
     * @return Response
     */
    public function highlightRandom(Request $request, $highlight_random = null, $channel = null)
    {
        $channel = $channel ?: $request->input('channel', null);
        $channelName = null;
        $limit = intval($request->input('count', 100));
        $offset = intval($request->input('offset', 0));
        $id = $request->input('id', 'false');

        if (empty($channel)) {
            $nb = new Nightbot($request);
            if (empty($nb->channel)) {
                return Helper::text('A channel name has to be specified.');
            }

            $channel = $nb->channel['providerId'];
            $id = 'true';
        }

        if ($id !== 'true') {
            try {
                // Store channel name separately and override $channel
                $channelName = $channel;
                $channel = $this->userByName($channel)->id;
            } catch (Exception $e) {
                return Helper::text($e->getMessage());
            }
        }

        $data = $this->twitchApi->videos($request, $channel, ['highlight'], $limit, $offset, $this->version);

        if (!empty($data['status'])) {
            return Helper::text($data['message'], $data['status']);
        }

        if (empty($data['videos'])) {
            return Helper::text(($channelName ?: $channel) . ' has no saved highlights.');
        }

        $highlights = $data['videos'];
        $random = array_rand($highlights);
        $vid = $highlights[$random];
        $format = '%s: %s';
        $text = [
            $vid['title'],
            $vid['url']
        ];

        if ($request->exists('game')) {
            array_unshift($text, $vid['game']);
            $format = '%s - ' . $format;
        }

        return Helper::text(vsprintf($format, $text));
    }

    /**
     * Return list of hosts for a channel
     *
     * @param  Request $request
     * @param  string  $hosts
     * @param  string  $channel Channel name
     * @return Response
     */
    public function hosts(Request $request, $hosts = null, $channel = null)
    {
        $channel = $channel ?: $request->input('channel', null);
        $channelName = null;
        $wantsJson = true;
        $displayNames = $request->exists('display_name');
        $id = $request->input('id', 'false');
        $limit = intval($request->input('limit', 0));
        $separator = $request->input('separator', ', ');

        if ($request->exists('list') || $request->exists('implode') || $request->exists('limit')) {
            $wantsJson = false;
        }

        $nb = new Nightbot($request);
        if (empty($channel)) {
            $message = 'Channel cannot be empty';
            if ($wantsJson) {
                return $this->errorJson(['message' => $message, 'status' => 404], 404);
            }

            if (empty($nb->channel)) {
                return Helper::text($message);
            }

            $channel = $nb->channel['providerId'];
            $id = 'true';
        }

        if ($id !== 'true') {
            try {
                // Store channel name separately and override $channel
                $channelName = $channel;
                $channel = $this->userByName($channel)->id;
            } catch (Exception $e) {
                return Helper::text($e->getMessage());
            }
        }

        $hosts = $this->twitchApi->hosts($channel);
        if (!empty($hosts['status'])) {
            $message = $hosts['message'];
            $code = $hosts['status'];
            if ($wantsJson) {
                return $this->errorJson(['message' => $message, 'status' => $code], $code);
            }

            // Return 200 if it's a Nightbot request to prevent "Remote Server Returned Code 404"
            return Helper::text($message, (empty($nb->channel) ? $code : 200));
        }

        if (empty($hosts)) {
            if ($wantsJson) {
                return Helper::json([]); // just send an empty host list
            }

            return Helper::text('No one is currently hosting ' . ($channelName ?: $channel));
        }

        $hostList = [];
        foreach($hosts as $host) {
            if ($displayNames) {
                $hostList[] = $host['host_display_name'];
            } else {
                $hostList[] = $host['host_login'];
            }
        }

        if ($wantsJson) {
            return Helper::json($hostList);
        }

        $implode = $request->exists('implode') || $request->exists('limit') ? $separator : PHP_EOL;
        if ($limit <= 0 || count($hostList) <= $limit) {
            return Helper::text(implode($implode, $hostList));
        }

        $names = array_slice($hostList, 0, $limit);
        $others = count($hostList) - $limit;
        $format = '%s and %d other' . ($others > 1 ? 's' : '');
        $text = sprintf($format, implode($separator, $names), $others);
        return Helper::text($text);
    }

    /**
     * Returns the amount of channels that is currently hosting a channel (or an error message).
     *
     * @param  Request $request
     * @param  string  $channel
     * @return Response
     */
    public function hostscount(Request $request, $channel = null)
    {
        $channel = $channel ?: $request->input('channel', null);
        $id = $request->input('id', 'false');

        if (empty($channel)) {
            return Helper::text('Channel name cannot be empty.');
        }

        if ($id !== 'true') {
            try {
                $channel = $this->userByName($channel)->id;
            } catch (Exception $e) {
                return Helper::text($e->getMessage());
            }
        }

        $hosts = $this->twitchApi->hosts($channel);
        return Helper::text(count($hosts));
    }

    /**
     * Returns the user's unique ID.
     *
     * @param  Request $request
     * @param  string  $user    Username of user
     * @return Response
     */
    public function id(Request $request, $user = null)
    {
        $user = $user ?: $request->input('user', null);

        if (empty($user)) {
            return Helper::text('Username has to be specified.');
        }

        try {
            $data = $this->userByName($user);
            return Helper::text($data->id);
        } catch (Exception $e) {
            return Helper::text($e->getMessage());
        }
    }

    /**
     * Returns list of ingest servers, plus their templates and availabilities.
     *
     * @return Response
     */
    public function ingests()
    {
        $ingests = $this->twitchApi->ingests();
        if (empty($ingests['ingests'])) {
            return $this->error('An error occurred attempting to load the data.');
        }

        $info = "";
        $pad = "    ";
        foreach ($ingests['ingests'] as $server) {
            $info .= "Name: " . $server['name'] . PHP_EOL;
            $info .= $pad . "Template: " . $server['url_template'] . PHP_EOL;
            $info .= $pad . "Availability: " . ($server['availability'] ? "Yes" : "No") . PHP_EOL . PHP_EOL;
        }

        return Helper::text($info);
    }

    /**
     * Returns a "multi stream" URL based on input streams.
     *
     * @param  Request $request
     * @param  string  $streams
     * @return Response
     */
    public function multi(Request $request, $streams = null)
    {
        $query = $request->input('query', null);
        $service = strtolower($request->input('service', 'multistream'));
        $streams = $streams ?: $request->input('streams', null);

        $services = [
            'multitwitch' => [
                'link' => "http://multitwitch.tv"
            ],
            'kadgar' => [
                'link' => 'http://kadgar.net/live'
            ],
            'multistream' => [
                'link' => 'https://multistre.am',
                'suffix' => '/layout{NUM}/',
                'multipliers' => [
                    1 => '3',
                    2 => '4',
                    3 => '7',
                    4 => '10',
                    5 => '14'
                ]
            ]
        ];

        $services['kbmod'] = $services['multistream'];
        $services['multistre.am'] = $services['multistream'];

        if (empty($services[$service])) {
            return Helper::text('Invalid service specified - Available services: ' . implode(", ", array_keys($services)));
        }

        if (empty($streams)) {
            return Helper::text('You have to specify which streams to create a multi link for (space-separated list).');
        }

        $service = $services[$service];
        $streams = explode(" ", $streams);
        $link = $service['link'];
        $prefix = empty($service['prefix']) ? '/' : $service['prefix'];
        $suffix = empty($service['suffix']) ? '' : $service['suffix'];

        foreach ($streams as $stream) {
            $link .= sprintf('%s%s', $prefix, $stream);
        }

        if (!empty($service['multipliers'])) {
            $multipliers = $service['multipliers'];
            $count = count($streams);
            $replaceWith = empty($multipliers[$count]) ? '' : $multipliers[$count];
            $suffix = str_replace('{NUM}', $replaceWith, $suffix);
        }

        $link .= $suffix;

        return Helper::text($link);
    }

    /**
     * Get a random subscriber based on the OAuth token.
     *
     * @param  Request $request
     * @return Response
     */
    public function randomSub(Request $request)
    {
        $token = $request->input('token', null);
        $amount = intval($request->input('count', 1));
        $field = $request->input('field', 'name');
        $separator = $request->input('separator', ', ');

        if (empty($token)) {
            return Helper::text('An OAuth token has to be specified.');
        }

        $tokenData = $this->twitchApi->base($token, $this->version)['token'];

        if ($tokenData['valid'] === false) {
            return Helper::text('The specified OAuth token is invalid.');
        }

        $scopes = $tokenData['authorization']['scopes'];

        if (!in_array('channel_subscriptions', $scopes)) {
            return Helper::text('The OAuth token is missing a required scope: channel_subscriptions');
        }

        $limit = 100;
        $data = $this->twitchApi->channelSubscriptions($tokenData['user_id'], $token, $limit, 0, $direction = 'asc', $this->version);

        if (!empty($data['message'])) {
            return Helper::text('An error occurred retrieving data from the API: ' . $data['message']);
        }

        $count = $data['_total'];

        if ($amount > $count) {
            return Helper::text(sprintf('Count specified (%d) is higher than the amount of subscribers (%d) this channel has!', $amount, $count));
        }

        $subscriptions = $data['subscriptions'];
        $offset = 0;
        if ($count > $limit) {
            while ($offset < $count) {
                $offset += 100;
                $data = $this->twitchApi->channelSubscriptions($tokenData['user_id'], $token, $limit, $offset, $direction = 'asc', $this->version);
                $subscriptions = array_merge($subscriptions, $data['subscriptions']);
            }
        }

        shuffle($subscriptions);
        $output = [];

        for ($i = 0; $i < $amount; $i++) {
            $count = count($subscriptions);
            $index = mt_rand(0, $count - 1);
            $sub = $subscriptions[$index]['user'];

            if (isset($sub[$field])) {
                $output[] = $sub[$field];
            }

            unset($subscriptions[$index]);
            shuffle($subscriptions); // Reset array keys
        }

        return Helper::text(implode($separator, $output));
    }

    /**
     * Picks a random user logged into the specified channel's chat.
     *
     * @param  Request $request
     * @param  string  $channel
     * @return Response
     */
    public function randomUser(Request $request, $channel = null)
    {
        if (empty($channel)) {
            return Helper::text('Channel name cannot be empty.');
        }

        // Specific _users_ to exclude.
        $exclude = $request->input('exclude', '');
        $exclude = array_map('trim', explode(',', $exclude));

        // "Groups" of chatters to ignore.
        $ignore = $request->input('ignore', '');
        $ignore = array_map('trim', explode(',', $ignore));

        $data = $this->twitchApi->get('https://tmi.twitch.tv/group/user/' . $channel . '/chatters', true);

        if (empty($data) || empty($data['chatters'])) {
            return Helper::text('There was an error retrieving users for channel: ' . $channel);
        }

        $users = [];
        foreach ($data['chatters'] as $group => $chatters) {
            if (!in_array($group, $ignore)) {
                $users = array_merge($users, $chatters);
            }
        }

        if (empty($users)) {
            return Helper::text('The list of users is empty.');
        }

        foreach ($exclude as $user) {
            $user = strtolower($user);
            $search = array_search($user, $users);

            if ($search === false) {
                continue;
            }

            unset($users[$search]);
        }

        shuffle($users);
        $rand = mt_rand(0, count($users) - 1);
        return Helper::text($users[$rand]);
    }

    /**
     * Gets the subscriber count of the specified channel
     *
     * @param  Request $request
     * @param  string  $subcount
     * @param  string  $channel  Channel to check subscriber count for
     * @return mixed
     */
    public function subcount(Request $request, $subcount = null, $channel = null)
    {
        $channel = $channel ?: $request->input('channel', null);
        $subtract = intval($request->input('subtract', 0));
        $id = $request->input('id', 'false');

        if ($request->exists('logout')) {
            return redirect()->route('auth.twitch.logout');
        }

        if (empty($channel) && !Auth::check()) {
            return Helper::text('Use ?channel=CHANNEL_NAME or /twitch/subcount/CHANNEL_NAME to get subcount.');
        }

        if (!empty($channel)) {
            $channel = strtolower($channel);
            $reAuth = route('auth.twitch.base') . '?redirect=subcount&scopes=user_read+channel_subscriptions';
            $needToReAuth = sprintf('%s needs to authenticate to use subcount: %s', $channel, $reAuth);

            try {
                $user = $id === 'true' ? User::where('id', $channel)->first() : $this->userByName($channel)->user;
            } catch (Exception $e) {
                $field = $id === 'true' ? 'ID' : 'username';
                return Helper::text('An error occurred when trying to find a channel with the ' . $field . ': ' . $channel);
            }

            if (empty($user)) {
                return Helper::text($needToReAuth);
            }

            try {
                $token = Crypt::decrypt($user->access_token);
            } catch (DecryptException $e) {
                // Something weird happened with the encrypted token
                // request channel owner to re-auth so it's encrypted properly
                return Helper::text($needToReAuth);
            }

            if (empty($token)) {
                return Helper::text($needToReAuth);
            }

            $data = $this->twitchApi->channelSubscriptions($user->id, $token, 1, 0, 'asc', $this->version);

            if (!empty($data['status'])) {
                if ($data['status'] === 401) {
                    return Helper::text($needToReAuth);
                }

                return Helper::text($data['message']);
            }

            return Helper::text($data['_total'] - $subtract);
        }

        $user = Auth::user();
        $data = [
            'page' => 'Subcount',
            'route' => route('twitch.subcount', ['subcount', $user->twitch->username])
        ];
        return view('twitch.subcount', $data);
    }

    /**
     * Retrieves the channel's subscriber emotes.
     *
     * @param  Request $request
     * @param  string  $channel
     * @return Response
     */
    public function subEmotes(Request $request, $channel = null)
    {
        $channel = $channel ?: $request->input('channel', null);
        $wantsJson = (($request->wantsJson() || $request->exists('json')) ? true : false);
        $id = $request->input('id', 'false');

        if (empty($channel)) {
            $message = 'Channel name is not specified';
            if ($wantsJson) {
                return $this->errorJson(['message' => $message, 'status' => 404], 404);
            }

            $nb = new Nightbot($request);
            if (empty($nb->channel)) {
                return Helper::text($message);
            }

            $channel = $nb->channel['name'];
            $id = 'false';
        }

        if ($id === 'true') {
            $channel = $this->twitchApi->channels($channel, $this->version);

            if (!empty($channel['message'])) {
                return Helper::text($channel['message']);
            }

            $channel = $channel['name'];
        }

        $emoticons = $this->twitchApi->emoticons($channel);

        if (!empty($emoticons['error'])) {
            $status = $emoticons['status'];
            $message = $emoticons['message'];
            if ($wantsJson) {
                return $this->errorJson(['error' => $emoticons['error'], 'message' => $message, 'status' => $status], $status);
            }

            return Helper::text($message);
        }

        $emotes = [];
        foreach ($emoticons['emoticons'] as $emote) {
            if ($emote['subscriber_only']) {
                $emotes[] = $emote['regex'];
            } else {
                break; // Subscriber emotes are always listed first.
            }
        }

        if (empty($emotes)) {
            $message = 'This channel does not have any subscriber emotes.';
            if ($wantsJson) {
                return $this->errorJson(['message' => $message], 404);
            }

            return Helper::text($message);
        }

        if ($wantsJson) {
            return $this->json([
                'emotes' => $emotes
            ]);
        }

        return Helper::text(implode(' ', $emotes));
    }

    /**
     * Returns a list of team members
     *
     * @param Request $request
     * @param string $team_members Route name
     * @param string $team Team identifier
     * @return Response
     */
    public function teamMembers(Request $request, $team_members = null, $team = null)
    {
        $wantsJson = ($request->exists('text') || $request->exists('implode') ? false : true);
        $settings = explode(',', $request->input('settings', ''));
        $team = $team ?: $request->input('team', null);

        if (empty($team)) {
            $message = 'Team identifier is empty';

            if ($wantsJson) {
                return Helper::json(['message' => $message, 'status' => 404], 404);
            }

            return Helper::text($message);
        }

        $checkTeam = $this->twitchApi->team($team, $this->version);
        if (!empty($checkTeam['status'])) {
            $message = $checkTeam['message'];
            $code = $checkTeam['status'];

            if ($wantsJson) {
                return Helper::json([
                    'message' => $message,
                    'status' => $code
                ], $code);
            }

            return Helper::text($message, $code);
        }

        $users = $checkTeam['users'];
        $members = [];
        foreach ($users as $user) {
            $members[] = (in_array('display_names', $settings) ? $user['display_name'] : $user['name']);
        }

        if ($request->exists('sort')) {
            sort($members);
        }

        if ($wantsJson) {
            return Helper::json($members);
        }

        $implode = $request->input('implode', PHP_EOL);
        return Helper::text(implode($implode, $members));
    }

    /**
     * Returns the total views the channel has.
     *
     * @param  Request $request
     * @param  string  $channel
     * @return Response
     */
    public function totalViews(Request $request, $channel = null)
    {
        $id = $request->input('id', 'false');
        $channelName = null;

        if (empty($channel)) {
            $nb = new Nightbot($request);
            if (empty($nb->channel)) {
                return Helper::text('A channel has to be specified.');
            }

            $channel = $nb->channel['providerId'];
            $id = 'true';
        }

        if ($id !== 'true') {
            try {
                // Store channel name separately and override $channel
                $channelName = $channel;
                $channel = $this->userByName($channel)->id;
            } catch (Exception $e) {
                return Helper::text($e->getMessage());
            }
        }

        $data = $this->twitchApi->channels($channel, $this->version);

        if (!empty($data['views'])) {
            return Helper::text($data['views']);
        }

        return Helper::text($data['error'] . ' - ' . $data['message']);
    }

    /**
     * Finds the specified channel's latest video upload.
     *
     * @param  Request $request
     * @param  String  $channel Channel name
     * @return Response
     */
    public function upload(Request $request, $channel = null)
    {
        $id = $request->input('id', 'false');
        $channelName = null;

        if (empty($channel)) {
            $nb = new Nightbot($request);
            if (empty($nb->channel)) {
                return Helper::text('A channel has to be specified.');
            }

            $channel = $nb->channel['providerId'];
            $id = 'true';
        }

        if ($id !== 'true') {
            try {
                // Store channel name separately and override $channel
                $channelName = $channel;
                $channel = $this->userByName($channel)->id;
            } catch (Exception $e) {
                return Helper::text($e->getMessage());
            }
        }

        $video = $this->twitchApi->videos($request, $channel, ['upload'], 1, 0, $this->version);

        if (!empty($video['status'])) {
            return Helper::text($video['message']);
        }

        if (empty($video['videos'])) {
            return Helper::text(($channelName ?: $channel) . ' has no uploaded videos.');
        }

        $upload = $video['videos'][0];
        $text = sprintf('%s - %s', $upload['title'], $upload['url']);
        return Helper::text($text);
    }

    /**
     * Checks the uptime of the current stream.
     *
     * @param Request $request
     * @param string $uptime
     * @param string $channel Channel name
     * @return Response
     */
    public function uptime(Request $request, $uptime = null, $channel = null)
    {
        $channel = $channel ?: $request->input('channel', null);
        $channelName = null;
        $id = $request->input('id', 'false');
        $precision = intval($request->input('precision', 4));

        if (empty($channel)) {
            $nb = new Nightbot($request);
            if (empty($nb->channel)) {
                return Helper::text('Channel cannot be empty');
            }

            $channel = $nb->channel['providerId'];
            $id = 'true';
        }

        if ($id !== 'true') {
            try {
                // Store channel name separately and override $channel
                $channelName = $channel;
                $channel = $this->userByName($channel)->id;
            } catch (Exception $e) {
                return Helper::text($e->getMessage());
            }
        }

        $stream = $this->twitchApi->streams($channel, $this->version);

        if (!empty($stream['status'])) {
            return Helper::text($stream['message']);
        }

        if (empty($stream['stream'])) {
            $channel = $channelName ?: $channel;
            $offline = $channel . ' is offline';
            if (!empty($request->input('offline_msg', null))) {
                $offline = $request->input('offline_msg');
            }

            return Helper::text($offline);
        }

        $start = $stream['stream']['created_at'];
        $diff = Helper::getDateDiff($start, time(), $precision);
        return Helper::text($diff);
    }

    /**
     * Retrieves the viewer count of the specified channel.
     *
     * @param  Request $request
     * @param  string  $channel Channel name (or channel ID with "id=true")
     * @return Response
     */
    public function viewercount(Request $request, $channel = null)
    {
        $id = $request->input('id', 'false');

        if (empty($channel)) {
            $nb = new Nightbot($request);
            if (empty($nb->channel)) {
                return Helper::text('Channel cannot be empty');
            }

            $channel = $nb->channel['providerId'];
            $id = 'true';
        }

        if ($id !== 'true') {
            try {
                // Store channel name separately for potential messages and override $channel
                $channelName = $channel;
                $channel = $this->userByName($channel)->id;
            } catch (Exception $e) {
                return Helper::text($e->getMessage());
            }
        }

        $stream = $this->twitchApi->streams($channel, $this->version);

        if (!empty($stream['status'])) {
            return Helper::text($stream['message']);
        }

        if (empty($stream['stream'])) {
            $channel = $channelName ?: $channel;
            return Helper::text($channel . ' is offline');
        }

        $viewers = $stream['stream']['viewers'];
        return Helper::text($viewers);
    }
}
