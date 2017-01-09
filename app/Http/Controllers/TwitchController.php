<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\User;
use Auth;
use App\Helpers\Helper;
use App\Helpers\Nightbot;

use Carbon\Carbon;
use DateTimeZone;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\CssSelector;
use GuzzleHttp\Client;

use Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

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
            'emoteslots' => 'emoteslots/{CHANNEL}/{SUBS}',
            'followage' => 'followage/{CHANNEL}/{USER}',
            'followed' => 'followed/{USER}/{CHANNEL}',
            'game' => 'game/{CHANNEL}',
            'help' => 'help/{SEARCH}',
            'highlight' => 'highlight/{CHANNEL}',
            'hosts' => 'hosts/{CHANNEL}',
            'id' => 'id/{USER}',
            'ingests' => 'ingests',
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
     * Returns the Twitch chat cluster for the specified channel. Added purely for backwards compatibility as it's not necessary as of March 23rd 2016.
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
     * Uses the specified subscriber count to see how many subscribers are needed to open a certain amount of emoteslots.
     *
     * @param  Request $request
     * @param  string  $channel The channel name
     * @return Response
     */
    public function emoteslots(Request $request, $channel = null)
    {
        $nb = new Nightbot($request);
        $subs = $request->input('subscribers', null);

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

        $precision = intval($request->input('precision')) ? intval($request->input('precision')) : 2;

        if (empty($channel) || empty($user)) {
            $message = 'You need to specify both user and channel name';
            return $this->error($message);
        }

        if (strtolower($channel) === strtolower($user)) {
            return response('A user cannot follow themself.')->withHeaders($this->headers);
        }

        $getFollow = $this->twitchApi->followRelationship($user, $channel);

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
        if (empty($channel)) {
            return Helper::text('A channel name has to be specified.');
        }

        $getFollowers = $this->twitchApi->channelFollows($channel, 1);

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
        $tz = $request->input('tz', 'UTC');
        // https://secure.php.net/manual/en/timezones.php
        $allTimezones = DateTimeZone::listIdentifiers();

        if (empty($user) || empty($channel)) {
            return $this->error('You have to specify both user and channel name');
        }

        if (!in_array($tz, $allTimezones)) {
            return Helper::text('Invalid timezone specified: ' . $tz);
        }

        $getFollow = $this->twitchApi->followRelationship($user, $channel);

        if (strtolower($user) === strtolower($channel)) {
            return Helper::text('A user cannot follow themself.');
        }

        // If $user isn't following $channel, a 404 is returned.
        if (!empty($getFollow['status'])) {
            return Helper::text($getFollow['message']);
        }

        $time = Carbon::parse($getFollow['created_at']);
        $format = 'M j. Y - h:i:s A (e)';
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

        if (empty($channel)) {
            return Helper::text('A channel name has to be specified.');
        }

        if ($count > 100) {
            return Helper::text('Count cannot be more than 100.');
        }

        $followers = $this->twitchApi->channelFollows($channel, $count, $offset, $direction);

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
        if ($route !== 'game') {
            $route = 'status';
        }

        $channel = $channel ?: $request->input('channel', null);

        if (empty($channel)) {
            return Helper::text('Channel name has to be specified.');
        }

        $getGame = $this->twitchApi->channels($channel);

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
        // config/twitch.php
        $articles = config('twitch.help.articles');

        $lang = $request->input('lang', 'en');

        $prefix = 'https://help.twitch.tv/customer/' . $lang . '/portal/articles/';
        $data = [
            'url_template' => $prefix . '{id}',
            'articles' => $articles
        ];

        $json = $request->wantsJson();
        if ($request->exists('list')) {
            if ($json) {
                return $this->json($data);
            }

            $data = [
                'list' => $articles,
                'prefix' => $prefix,
                'page' => 'Help Articles'
            ];
            return view('shared.list', $data);
        }

        $msg = null;
        $code = null;

        $search = trim($search);
        if (empty($search) || strtolower($search) === 'list') {
            if ($json) {
                return $this->json($data);
            }
            return Helper::text('List of available help articles with titles: ' . route('twitch.help') . '?list');
        }

        $results = preg_grep('/(' . $search . ')/i', array_keys($articles));
        if (empty($results)) {
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

        $title = array_values($results)[0];
        $url = $prefix . $articles[$title];
        if ($json) {
            $data = [
                'code' => 200,
                'title' => $title,
                'url' => $url
            ];
            return Helper::json($data);
        }

        return Helper::text($title . " - " . $url);
    }

    /**
     * Returns the latest highlight of channel.
     * @param  Request $request
     * @param  string  $highlight
     * @param  string  $channel Channel name
     * @return Response           Latest highlight and title of highlight.
     */
    public function highlight(Request $request, $highlight = null, $channel = null)
    {
        $channel = $channel ?: $request->input('channel', null);

        if (empty($channel)) {
            return $this->error('You have to specify a channel');
        }

        $fetchHighlight = $this->twitchApi->videos($request, $channel, ['highlight']);

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
        $limit = intval($request->input('count', 100));
        $offset = intval($request->input('offset', 0));

        if (empty($channel)) {
            return Helper::text('A channel name has to be specified.');
        }

        $data = $this->twitchApi->videos($request, $channel, ['highlight'], $limit, $offset);

        if (!empty($data['status'])) {
            return Helper::text($data['message'], $data['status']);
        }

        if (empty($data['videos'])) {
            return Helper::text($channel . ' has no saved highlights.');
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
     * @param  Request $request
     * @param  string  $hosts
     * @param  string  $channel Channel name
     * @return Response
     */
    public function hosts(Request $request, $hosts = null, $channel = null)
    {
        $channel = $channel ?: $request->input('channel', null);
        $wantsJson = ($request->exists('list') || $request->exists('implode') ? false : true);
        $displayNames = $request->exists('display_name');
        if (empty($channel)) {
            $message = 'Channel cannot be empty';
            if($wantsJson) {
                return $this->errorJson(['message' => $message, 'status' => 404], 404);
            }
            return $this->error($message);
        }

        $hosts = $this->twitchApi->hosts($channel);
        if (!empty($hosts['status'])) {
            $message = $hosts['message'];
            $code = $hosts['status'];
            if ($wantsJson) {
                return $this->errorJson(['message' => $message, 'status' => $code], $code);
            }
            return $this->error($message, $code);
        }

        if (empty($hosts)) {
            if ($wantsJson) {
                return Helper::json([]); // just send an empty host list
            }
            return $this->error('No one is currently hosting ' . $channel);
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

        $implode = $request->exists('implode') ? ', ' : PHP_EOL;
        return Helper::text(implode($implode, $hostList));
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

        $data = $this->twitchApi->users($user);

        if (isset($data['error'])) {
            return Helper::text($data['message']);
        }

        return Helper::text($data['_id']);
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

        return response($info)->withHeaders($this->headers);
    }

    /**
     * Gets the subscriber count of the specified channel
     * @param  Request $request
     * @param  string  $subcount
     * @param  string  $channel  Channel to check subscriber count for
     * @return mixed
     */
    public function subcount(Request $request, $subcount = null, $channel = null)
    {
        $channel = $channel ?: $request->input('channel', null);

        if ($request->exists('logout')) {
            return redirect()->route('auth.twitch.logout');
        }

        if (empty($channel) && !Auth::check()) {
            return Helper::text('Use ?channel=CHANNEL_NAME or /twitch/subcount/CHANNEL_NAME to get subcount.');
        }

        if (!empty($channel)) {
            $channel = strtolower($channel);
            $reAuth = route('auth.twitch.base') . '?redirect=subcount&scopes=user_read+channel_subscriptions';
            $user = User::where('username', $channel)->first();
            $needToReAuth = sprintf('%s needs to authenticate to use subcount: %s', $channel, $reAuth);

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

            $data = $this->twitchApi->channelSubscriptions($channel, $token);

            if (!empty($data['status'])) {
                if ($data['status'] === 401) {
                    return Helper::text($needToReAuth);
                }

                return Helper::text($data['message']);
            }

            return Helper::text($data['_total']);
        }

        $user = Auth::user();
        $data = [
            'page' => 'Subcount',
            'route' => route('twitch.subcount', ['subcount', $user->username])
        ];
        return view('twitch.subcount', $data);
    }

    public function subEmotes(Request $request, $channel = null)
    {
        $channel = $channel ?: $request->input('channel', null);
        $wantsJson = (($request->wantsJson() || $request->exists('json')) ? true : false);

        if (empty($channel)) {
            $message = 'Channel name is not specified';
            if ($wantsJson) {
                return $this->errorJson(['message' => $message, 'status' => 404], 404);
            }
            return $this->error($message);
        }

        $emoticons = $this->twitchApi->emoticons($channel);

        if (!empty($emoticons['error'])) {
            $status = $emoticons['status'];
            $message = $emoticons['message'];
            if ($wantsJson) {
                return $this->errorJson(['error' => $emoticons['error'], 'message' => $message, 'status' => $status], $status);
            }
            return $this->error($message, $status);
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
            return $this->error($message);
        }

        if ($wantsJson) {
            return $this->json([
                'emotes' => $emotes
            ]);
        }
        return response(implode(' ', $emotes))->withHeaders($this->headers);
    }

    /**
     * Returns a list of team members
     * @param  Request $request
     * @param  string $team_members
     * @param  string $team Team identifier
     * @return Response
     */
    public function teamMembers(Request $request, $team_members = null, $team = null)
    {
        $wantsJson = ($request->exists('text') ? false : true);

        $team = $team ?: $request->input('team', null);
        if (empty($team)) {
            $message = 'Team identifier is empty';
            if ($wantsJson) {
                return $this->errorJson(['message' => $message, 'status' => 404], 404);
            }
            return $this->error($message);
        }

        $checkTeam = $this->twitchApi->team($team);
        if (!empty($checkTeam['status'])) {
            $message = $checkTeam['message'];
            $code = $checkTeam['status'];
            if($wantsJson) {
                return $this->errorJson(['message' => $message, 'status' => $code], $code);
            }
            return $this->error($message, $code);
        }

        function getPage($team, $page = '1')
        {
            $url = 'https://www.twitch.tv/team/' . $team .'/live_member_list?page=' . $page;
            $client = new Client;
            return $client->request('GET', $url, ['http_errors' => false]);
        }

        function getMembers($crawler)
        {
            return $crawler->filter('.member')->each(function (Crawler $node, $i) {
                return str_replace('channel_', null, $node->extract(['id'])[0]);
            });
        }

        $page = getPage($team);

        $body = (string) $page->getBody();
        $crawler = new Crawler($body);
        $members = getMembers($crawler);
        $checkPages = $crawler->filter('.page_data');
        if (!empty(trim($checkPages->text()))) {
            $pageCount = (int) $crawler->filter('.page_links')->filter('a')->eq(2)->text();
            for ($page = 2; $page <= $pageCount; $page++) {
                $req = getPage($team, $page);
                $body = (string) $req->getBody();
                $crawler = new Crawler($body);
                $members = array_merge($members, getMembers($crawler));
            }
        }

        if ($request->exists('sort')) {
            sort($members);
        }

        if ($wantsJson) {
            return response()->json($members)->header('Access-Control-Allow-Origin', '*');
        }
        return response(implode(PHP_EOL, $members))->withHeaders($this->headers);
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
        if (empty($channel)) {
            return Helper::text('A channel has to be specified.');
        }

        $video = $this->twitchApi->videos($request, $channel, ['upload']);

        if (!empty($video['status'])) {
            return Helper::text($video['message']);
        }

        if (empty($video['videos'])) {
            return Helper::text($channel . ' has no uploaded videos.');
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
        if (empty($channel)) {
            return $this->error('Channel cannot be empty');
        }

        $stream = $this->twitchApi->streams($channel);
        if (!empty($stream['status'])) {
            return $this->error($stream['message'], $stream['status']);
        }

        if (empty($stream['stream'])) {
            $offline = $channel . ' is offline';
            if (!empty($request->input('offline_msg', null))) {
                $offline = $request->input('offline_msg');
            }

            return $this->error($offline);
        }

        $start = $stream['stream']['created_at'];
        $diff = Helper::getDateDiff($start, time(), 4);
        return response($diff)->withHeaders($this->headers);
    }
}
