<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use DB;
use App\Helpers\Helper;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\CssSelector;
use GuzzleHttp\Client;

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
        return Helper::text($message, $code, $this->headers);
    }

    /**
     * Returns an error JSON response
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
     * @return response
     */
    public function base()
    {
        $baseUrl = url('/twitch/');

        $urls = [
            'followage' => 'followage/{CHANNEL}/{USER}',
            'followed' => 'followed/{USER}/{CHANNEL}',
            'highlight' => 'highlight/{CHANNEL}',
            'hosts' => 'hosts/{CHANNEL}',
            'ingests' => 'ingests',
            'subcount' => 'subcount/{CHANNEL}',
            'subscriber_emotes' => 'subscriber_emotes/{CHANNEL}',
            'team_members' => 'team_members/{TEAM_ID}',
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
     * Shows the date and time of when a user followed a channel.
     *
     * @param  Request $request
     * @param  string  $followed
     * @param  string  $user
     * @param  string  $channel
     * @return Response
     */
    public function followed(Request $request, $followed = null, $user = null, $channel = null)
    {
        $user = $user ?: $request->input('user', null);
        $channel = $channel ?: $request->input('channel', null);

        if (empty($user) || empty($channel)) {
            return $this->error('You have to specify both user and channel name');
        }

        $getFollow = $this->twitchApi->followRelationship($user, $channel);

        if (strtolower($user) === strtolower($channel)) {
            return response('A user cannot follow themself.')->withHeaders($this->headers);
        }

        // If $user isn't following $channel, a 404 is returned.
        if (!empty($getFollow['status'])) {
            return response($getFollow['message'])->withHeaders($this->headers);
        }

        $time = strtotime($getFollow['created_at']);
        $format = 'M j. Y - h:i:s A (e)';
        return response(date($format, $time))->withHeaders($this->headers);
    }

    public function help(Request $request, $search = null)
    {
        $articles = [
            'About Site Suspensions, DMCA Suspensions, and Chat Bans' => 1727973,
            'Authy FAQ' => 2186821,
            'Channel Banned Words' => 2100263,
            'Chat Commands' => 659095,
            'Chat Replay FAQ' => 2337148,
            'Cheering/Bits' => 2449458,
            'Creative FAQ' => 2176641,
            'Guide to Broadcast Health and Using Twitch Inspector' => 2420572,
            'Guide to Custom Resub Messages' => 2457351,
            'How to Edit Info Panels' => 2416760,
            'How to File a User Report' => 725568,
            'How to Handle Viewbots/Followbots' => 2435640,
            'How to Redeem Coupon Codes' => 2392092,
            'How to use Channel Feed' => 2377877,
            'How to Use Clips' => 2442508,
            'How to use the Friends Feature' => 2418761,
            'How To Use VOD Thumbnails' => 2218412,
            'List of Prohibited/Banned Games' => 1992676,
            'Moderation Team Building FAQ' => 1360598,
            'Partner Emoticon and Badge Guide' => 2348985,
            'Partner Help and Contact Information' => 735178,
            'Partner Payment FAQ' => 735169,
            'Partner Program Overview' => 735069,
            'Partner Revenue Guide' => 2347894,
            'Partner Settings Guide' => 2401004,
            'Purchase Support FAQ' => 2341636,
            'Social Eating FAQ' => 2483343,
            'Subscription Program FAQ' => 735176,
            'Terms of Service (ToS)' => 735191,
            'Tips for Applying to the Partner Program' => 735127,
            'Twitch Chat Badges Guide' => 659115,
            'Twitch Music FAQ' => 1824967,
            'Twitch Rules of Conduct (RoC)' => 983016,
            'Twitch Turbo' => 973896,
            'Twitch Twitter "@TwitchSupport" FAQ' => 1210307,
            'Two Factor Authentication (2FA) with Authy' => 2186271,
            'Username Rename (Name Changes) and Recycling Policies' => 1015624,
            'Whispers FAQ' => 2215236,
            // Sorted at the bottom for lower priority
            'Android Subscriptions FAQ' => 2297883,
            'Creative Commissions' => 2337107,
            'How to File a Whisper Report' => 2329782,
        ];

        $prefix = 'https://help.twitch.tv/customer/en/portal/articles/';

        $json = $request->wantsJson();
        if ($request->exists('list')) {
            if ($json) {
                $data = [
                    'url_template' => $prefix . '{id}',
                    'articles' => $articles
                ];
                return $this->json($data);
            }

            $list = '';
            foreach ($articles as $title => $id) {
                $list .= $title . ": " . $prefix . $id . PHP_EOL;
            }

            return Helper::text($list);
        }

        $msg = null;
        $code = null;

        $search = trim($search);
        if (empty($search)) {
            $msg = 'Search cannot be empty.';
            $code = 404;
        }

        if (strtolower($search) === 'list') {
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
     * @param  string  $hosts
     * @param  string  $channel Channel name
     * @return Response
     */
    public function hosts(Request $request, $hosts = null, $channel = null)
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
                return $this->errorJson($request, ['message' => $message, 'status' => $code], $code);
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
            return response()->json($hostList)->header('Access-Control-Allow-Origin', '*');
        }

        $implode = $request->exists('implode') ? ', ' : PHP_EOL;
        return response(implode($implode, $hostList))->withHeaders($this->headers);
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
            session()->flush();
        }

        if (empty($channel) && !session()->has('subcount_at')) {
            return response('Use ?channel=CHANNEL_NAME to get subcount.')->withHeaders($this->headers);
        }

        if (!empty($channel)) {
            $channel = strtolower($channel);
            $reAuth = url('/auth/twitch?page=subcount');
            $token = DB::table('twitch_subcount')->where('username', $channel)->get();
            if (empty($token)) {
                return $this->error($channel . ' needs to authenticate to use subcount: ' . $reAuth);
            }

            $accessToken = $token[0]->access_token;
            $subscriptionData = $this->twitchApi->channelSubscriptions($channel, $accessToken);
            if (!empty($subscriptionData['status'])) {
                if($subscriptionData['status'] === 401) {
                    return $this->error($channel . ' needs to re-authenticate to use subcount: ' . $reAuth);
                }
                return $this->error($subscriptionData['message'], $subscriptionData['status']);
            }
            return response($subscriptionData['_total'])->withHeaders($this->headers);
        }

        if (session()->has('subcount_at')) {
            $username = session('username');
            $token = session('subcount_at');
            $checkToken = DB::table('twitch_subcount')->where('username', $username)->get();
            if (empty($checkToken)) {
                DB::table('twitch_subcount')
                    ->insert(
                        ['username' => $username, 'access_token' => $token]
                    );
            } else {
                DB::table('twitch_subcount')
                    ->where('username', $username)
                    ->update(['access_token' => $token]);
            }
            return view('twitch.subcount', ['username' => $username]);
        }
    }

    public function subEmotes(Request $request, $channel = null)
    {
        $channel = $channel ?: $request->input('channel', null);
        $wantsJson = (($request->wantsJson() || $request->exists('json')) ? true : false);

        if (empty($channel)) {
            $message = 'Channel name is not specified';
            if ($wantsJson) {
                return $this->errorJson($request, ['message' => $message, 'status' => 404]);
            }
            return $this->error($message);
        }

        $emoticons = $this->twitchApi->emoticons($channel);

        if (!empty($emoticons['error'])) {
            $status = $emoticons['status'];
            $message = $emoticons['message'];
            if ($wantsJson) {
                return $this->errorJson($request, ['error' => $emoticons['error'], 'message' => $message, 'status' => $status]);
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
                return $this->errorJson($request, ['message' => $message]);
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
        $wantsJson = (($request->wantsJson() || $request->exists('json')) ? true : false);

        $team = $team ?: $request->input('team', null);
        if (empty($team)) {
            $message = 'Team identifier is empty';
            if ($wantsJson) {
                return $this->errorJson($request, ['message' => $message, 'status' => 404]);
            }
            return $this->error($message);
        }

        $checkTeam = $this->twitchApi->team($team);
        if (!empty($checkTeam['status'])) {
            $message = $checkTeam['message'];
            $code = $checkTeam['status'];
            if($wantsJson) {
                return $this->errorJson($request, ['message' => $message, 'status' => $code], $code);
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
            return response()->json([
                'members' => $members
            ])->header('Access-Control-Allow-Origin', '*');
        }
        return response(implode(PHP_EOL, $members))->withHeaders($this->headers);
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
