<?php

namespace App\Http\Controllers;

use App\Exceptions\TwitchFormatException;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

use App\CachedTwitchUser;
use App\User;
use Auth;
use App\Helpers\Helper;
use App\Helpers\Nightbot;
use App\TwitchHelpArticle as HelpArticle;

use App\Repositories\TwitchApiRepository;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use DateTimeZone;

use Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

use Cache;
use Exception;
use Log;

use App\Exceptions\TwitchApiException;

class TwitchController extends Controller
{
    /**
     * @var string
     */
    private $defaultAvatar = 'https://static-cdn.jtvnw.net/jtv-static/404_preview-300x300.png';

    /**
     * Scopes required for routes like 'subcount' and 'subage'
     *
     * @var string
     */
    private $subScopes = 'channel:read:subscriptions+user:read:email';

    /**
     * Scopes required for routes like 'followage' and 'followed'
     *
     * @var string
     */
    private $followScope = 'moderator:read:followers';

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
     * @var TwitchApiRepository
     */
    private $api;

    /**
     * Initializes the controller with a reference to TwitchApiController.
     *
     * @param TwitchApiRepository $apiRepository
     */
    public function __construct(TwitchApiRepository $apiRepository)
    {
        $this->api = $apiRepository;
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
     *
     * @param  string $name The username to look for.
     * @return object
     * @throws TwitchApiException
     */
    protected function userByName($name)
    {
        try {
            return $this->api->userByName($name);
        } catch (TwitchApiException $e) {
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
            'accountage' => 'accountage/{USER}',
            'avatar' => 'avatar/{USER}',
            'creation' => 'creation/{USER}',
            'followage' => 'followage/{CHANNEL}/{USER}',
            'followcount' => 'followcount/{CHANNEL}',
            'followed' => 'followed/{USER}/{CHANNEL}',
            'followers' => 'followers/{CHANNEL}',
            'following' => 'following/{USER}',
            'game' => 'game/{CHANNEL}',
            'help' => 'help/{SEARCH}',
            'highlight' => 'highlight/{CHANNEL}',
            'highlight_random' => 'highlight_random/{CHANNEL}',
            'hosts' => 'hosts/{CHANNEL}',
            'hostscount' => 'hostscount/{CHANNEL}',
            'id' => 'id/{USER}',
            'ingests' => 'ingests',
            'latest_sub' => 'latest_sub/{CHANNEL}',
            'multi' => 'multi/{STREAMS}',
            'random_sub' => 'random_sub/{CHANNEL}',
            'random_user' => 'random_user/{CHANNEL}',
            'subcount' => 'subcount/{CHANNEL}',
            'subpoints' => 'subpoints/{CHANNEL}',
            'subscriber_emotes' => 'subscriber_emotes/{CHANNEL}',
            'status' => 'status/{CHANNEL}',
            'title' => 'title/{CHANNEL}',
            'team_members' => 'team_members/{TEAM_ID}',
            'total_views' => 'total_views/{CHANNEL}',
            'uptime' => 'uptime/{CHANNEL}',
            'viewercount' => 'viewercount/{CHANNEL}',
            'videos' => 'videos/{CHANNEL}',
            'vod_replay' => 'vod_replay/{CHANNEL}',
        ];

        $urls = array_map(function($path) use($baseUrl) {
            return sprintf('%s/%s', $baseUrl, $path);
        }, $urls);

        return $this->json([
            'documentation' => 'https://docs.decapi.me/twitch',
            'endpoints' => $urls,
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
                return Helper::text(__('generic.username_required'));
            }

            $user = $user ?: $nb->user['providerId'];
            $id = 'true';
        }

        /**
         * Retrieve from API if not in cache
         */
        $user = strtolower(trim($user));
        $cacheKey = sprintf('twitch_user-created_%s', $user);
        if (!Cache::has($cacheKey)) {
            try {
                if ($id !== 'true') {
                    $userInfo = $this->api->userByUsername($user);
                }
                else {
                    $userInfo = $this->api->userById($user);
                }

                if (empty($userInfo)) {
                    return Helper::text(__('twitch.user_not_found', [
                        'user' => $user,
                    ]));
                }

                $timestamp = $userInfo['created_at'];
                Cache::put($cacheKey, $timestamp, config('twitch.cache.created'));
            }
            catch (TwitchApiException $ex) {
                return Helper::text('[Error from Twitch API] ' . $ex->getMessage());
            }
        }

        $timestamp = Cache::get($cacheKey);
        $time = Helper::getDateDiff($timestamp, time(), $precision);
        return Helper::text($time);
    }

    /**
     * Retrieves the URL for the user's avatar.
     *
     * @param  Request $request
     * @param  string  $user    Username/ID
     * @return Response
     */
    public function avatar(Request $request, $user = null)
    {
        $user = $user ?? $request->input('user', null);
        if (empty($user)) {
            return Helper::text(__('generic.username_required'));
        }

        /**
         * Return avatar URL from cache if it exists.
         */
        $cacheKey = sprintf('twitch_avatar_%s', md5($user));
        if (Cache::has($cacheKey)) {
            return Helper::text(Cache::get($cacheKey));
        }

        $id = $request->input('id', 'false');
        try {
            $data = $id === 'true' ? $this->api->userById($user) : $this->api->userByUsername($user);
        }
        catch (TwitchApiException $ex)
        {
            return Helper::text('Invalid Twitch user specified: ' . $user, 400);
        }
        catch (Exception $ex)
        {
            Log::error($ex->getMessage());
            return Helper::text('Error occurred retrieving user information for Twitch user: ' . $user);
        }

        if (empty($data)) {
            return Helper::text(__('twitch.user_not_found', [
                'user' => $user,
            ]));
        }

        // Fallback to the default avatar if necessary.
        $avatar = $data['avatar'] ?? $this->defaultAvatar;

        // Cache the avatar URL for 5 minutes.
        Cache::put($cacheKey, $avatar, config('twitch.cache.avatar'));

        return Helper::text($avatar);
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

        /**
         * Check if specified timezone is valid.
         */
        if (!in_array($tz, $allTimezones)) {
            return Helper::text(__('time.invalid_timezone', ['timezone' => $tz]));
        }

        if (empty($user)) {
            $nb = new Nightbot($request);
            if (empty($nb->user)) {
                return Helper::text(__('generic.username_required'));
            }

            $user = $user ?: $nb->user['providerId'];
            $id = 'true';
        }

        /**
         * Retrieve from API if not in cache
         */
        $user = strtolower(trim($user));
        $cacheKey = sprintf('twitch_user-created_%s', $user);
        if (!Cache::has($cacheKey)) {
            try {
                if ($id !== 'true') {
                    $userInfo = $this->api->userByUsername($user);
                }
                else {
                    $userInfo = $this->api->userById($user);
                }

                if (empty($userInfo)) {
                    return Helper::text(__('twitch.user_not_found', [
                        'user' => $user,
                    ]));
                }

                $timestamp = $userInfo['created_at'];
                Cache::put($cacheKey, $timestamp, config('twitch.cache.created'));
            }
            catch (TwitchApiException $ex) {
                return Helper::text('[Error from Twitch API] ' . $ex->getMessage());
            }
        }

        $timestamp = Cache::get($cacheKey);
        $time = Carbon::parse($timestamp);
        $time->setTimezone($tz);

        return Helper::text($time->format($format));
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

        $authUrl = sprintf('%s?redirect=followage&scopes=%s', route('auth.twitch.base'), $this->followScope);
        $needToAuth = sprintf(__('twitch.followage_needs_authentication'), $authUrl);

        if (empty($channel) && Auth::check()) {
            $user = Auth::user();
            $apiToken = $user->api_token;

            $slcbRoute = sprintf('%s?token=%s', route('twitch.followage', ['channel' => '$mychannel', 'user' => '$touserid']), $apiToken);
            $nightbotRoute = sprintf('%s?token=%s', route('twitch.followage', ['channel' => '$(channel)', 'user' => '$(touser)']), $apiToken);
            $data = [
                'page' => 'Followage',
                'slcbRoute' => urldecode($slcbRoute),
                'nightbotRoute' => urldecode($nightbotRoute),
                'apiToken' => $apiToken,
            ];

            return view('twitch.followage', $data);
        }

        $apiToken = $request->input('token', null);
        if (empty($apiToken)) {
            $tokenMessage = __('twitch.follow_token_parameter', ['endpoint' => '/twitch/followage']);
            return Helper::text(sprintf('%s - %s', $tokenMessage, $needToAuth));
        }

        $precision = intval($request->input('precision')) ? intval($request->input('precision')) : 2;
        if (empty($channel) || empty($user)) {
            $nb = new Nightbot($request);
            if (empty($nb->channel) || empty($nb->user)) {
                return Helper::text(__('generic.user_channel_name_required'));
            }

            $channel = $channel ?: $nb->channel['providerId'];
            $user = $user ?: $nb->user['providerId'];
            $id = 'true';
        }

        $channel = trim($channel);
        $user = trim($user);

        if (strtolower($channel) === strtolower($user)) {
            return Helper::text(__('twitch.cannot_follow_self'));
        }

        /**
         * Save username/channel name in separate variables before
         * overwriting them to use in later calls.
         */
        $username = $user;
        $channelName = $channel;
        if ($id !== 'true') {
            try {
                $channel = $this->userByName($channel)->id;
                $user = $this->userByName($user)->id;
            } catch (Exception $e) {
                return Helper::text($e->getMessage());
            }
        }

        $tokenUser = User::where('api_token', $apiToken)->first();
        if (empty($tokenUser)) {
            return Helper::text('Invalid `token` parameter provided. No user found with this token.');
        }

        $combinedScopes = sprintf('%s+%s', $tokenUser->scopes, $this->followScope);
        $authUrl = sprintf('%s?redirect=followage&scopes=%s', route('auth.twitch.base'), $combinedScopes);

        $cachedUser = null;
        try {
            $cachedUser = $this->api->cachedUserById($tokenUser->id);
        }
        catch (TwitchApiException $ex) {
            return Helper::text('[Error from Twitch API] ' . $ex->getMessage());
        }

        $needToReAuth = sprintf(__('twitch.followage_needs_reauthentication'), $cachedUser->username, $authUrl);

        $scopes = explode('+', $tokenUser->scopes);
        if (!in_array($this->followScope, $scopes)) {
            return Helper::text($needToReAuth);
        }

        $twitchToken = Crypt::decrypt($tokenUser->access_token);
        $this->api->setToken($twitchToken);

        try {
            $follow = $this->api->channelFollowers($channel, $user);
        }
        catch (TwitchApiException $ex)
        {
            return Helper::text('[Error from Twitch API] ' . $ex->getMessage());
        }
        catch (Exception $ex) {
            return Helper::text(sprintf('An error has occurred requesting followage between %s (channel) and %s (user)', $channelName, $username));
        }

        if (empty($follow['followers'])) {
            return Helper::text(__('twitch.follow_not_found', [
                'user' => $username,
                'channel' => $channelName,
            ]));
        }

        $follow = $follow['followers'][0];
        return Helper::text(Helper::getDateDiff($follow['followed_at'], time(), $precision));
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
                return Helper::text(__('generic.channel_name_required'));
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

        $cacheKey = sprintf('twitch_followcount_%s', $channel);
        if (Cache::has($cacheKey)) {
            return Helper::text(Cache::get($cacheKey));
        }

        try {
            $getFollowers = $this->api->channelFollowers($channel);
        }
        catch (TwitchApiException $ex)
        {
            // ¯\_(ツ)_/¯
            return Helper::text('[Error from Twitch API] ' . $ex->getMessage());
        }
        catch (Exception $ex)
        {
            return Helper::text('An error has occurred requesting followcount for: ' . $channel);
        }

        if (empty($getFollowers)) {
            return Helper::text('An error has occurred requesting followcount for: ' . $channel);
        }

        $count = $getFollowers['total'];
        Cache::put($cacheKey, $count, config('twitch.cache.followcount'));
        return Helper::text($count);
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

        $authUrl = sprintf('%s?redirect=followed&scopes=%s', route('auth.twitch.base'), $this->followScope);
        $needToAuth = sprintf(__('twitch.followed_needs_authentication'), $authUrl);

        if (empty($channel) && Auth::check()) {
            $user = Auth::user();
            $apiToken = $user->api_token;

            $slcbRoute = sprintf('%s?token=%s', route('twitch.followed', ['followed' => 'followed', 'channel' => '$mychannel', 'user' => '$touserid']), $apiToken);
            $nightbotRoute = sprintf('%s?token=%s', route('twitch.followed', ['followed' => 'followed', 'channel' => '$(channel)', 'user' => '$(touser)']), $apiToken);
            $data = [
                'page' => 'Followed',
                'slcbRoute' => urldecode($slcbRoute),
                'nightbotRoute' => urldecode($nightbotRoute),
                'apiToken' => $apiToken,
            ];

            return view('twitch.followed', $data);
        }

        $apiToken = $request->input('token', null);
        if (empty($apiToken)) {
            $tokenMessage = __('twitch.follow_token_parameter', ['endpoint' => '/twitch/followed']);
            return Helper::text(sprintf('%s - %s', $tokenMessage, $needToAuth));
        }

        if (empty($user) || empty($channel)) {
            $nb = new Nightbot($request);
            if (empty($nb->channel) || empty($nb->user)) {
                return Helper::text(__('generic.user_channel_name_required'));
            }

            $channel = $channel ?: $nb->channel['providerId'];
            $user = $user ?: $nb->user['providerId'];
            $id = 'true';
        }

        if (!in_array($tz, $allTimezones)) {
            return Helper::text(__('time.invalid_timezone', ['timezone' => $tz]));
        }

        if (strtolower($user) === strtolower($channel)) {
            return Helper::text(__('twitch.cannot_follow_self'));
        }

        /**
         * Set different variables for error messages (usernames instead of IDs).
         */
        $channelName = $channel;
        $username = $user;
        if ($id !== 'true') {
            try {
                $channel = $this->userByName($channel)->id;
                $user = $this->userByName($user)->id;
            } catch (Exception $e) {
                return Helper::text($e->getMessage());
            }
        }

        $tokenUser = User::where('api_token', $apiToken)->first();
        if (empty($tokenUser)) {
            return Helper::text('Invalid `token` parameter provided. No user found with this token.');
        }

        $combinedScopes = sprintf('%s+%s', $tokenUser->scopes, $this->followScope);
        $authUrl = sprintf('%s?redirect=followage&scopes=%s', route('auth.twitch.base'), $combinedScopes);

        $cachedUser = null;
        try {
            $cachedUser = $this->api->cachedUserById($tokenUser->id);
        }
        catch (TwitchApiException $ex) {
            return Helper::text('[Error from Twitch API] ' . $ex->getMessage());
        }

        $needToReAuth = sprintf(__('twitch.followage_needs_reauthentication'), $cachedUser->username, $authUrl);

        $scopes = explode('+', $tokenUser->scopes);
        if (!in_array($this->followScope, $scopes)) {
            return Helper::text($needToReAuth);
        }

        $twitchToken = Crypt::decrypt($tokenUser->access_token);
        $this->api->setToken($twitchToken);

        try {
            $follow = $this->api->channelFollowers($channel, $user);
        }
        catch (TwitchApiException $ex)
        {
            return Helper::text('[Error from Twitch API] ' . $ex->getMessage());
        }
        catch (Exception $ex) {
            return Helper::text(sprintf('An error has occurred requesting followage between %s (channel) and %s (user)', $channelName, $username));
        }

        if (empty($follow['followers'])) {
            return Helper::text(__('twitch.follow_not_found', [
                'user' => $username,
                'channel' => $channelName,
            ]));
        }

        $follow = $follow['followers'][0];

        $time = Carbon::parse($follow['followed_at']);
        $time->setTimezone($tz);

        return Helper::text($time->format($format));
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
                return Helper::text(__('generic.channel_name_required'));
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

        /**
         * Check cache for game/status.
         */
        $cacheId = md5($channel);
        $cacheGame = sprintf('twitch_game_%s', $cacheId);
        $cacheStatus = sprintf('twitch_status_%s', $cacheId);

        $cacheKey = $route === 'game' ? $cacheGame : $cacheStatus;
        if (Cache::has($cacheKey)) {
            return Helper::text(Cache::get($cacheKey));
        }

        try {
            $getChannel = $this->api->channelById($channel);
        } catch (TwitchApiException $ex) {
            return Helper::text(__('generic.error_loading_data_api'));
        } catch (TwitchFormatException $ex) {
            return Helper::text($ex->getMessage());
        }

        $game = $getChannel['game']['name'];
        $status = $getChannel['title'];

        /**
         * We can cache both values as it's from the same request anyways.
         */
        Cache::put($cacheGame, $game, config('twitch.cache.game'));
        Cache::put($cacheStatus, $status, config('twitch.cache.status'));

        if ($route === 'game') {
            return Helper::text($game ?: '');
        }

        return Helper::text($status);
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
                'page' => __('twitch.help_articles'),
                'prefix' => $prefix
            ];
            return view('shared.list', $data);
        }

        $msg = null;
        $code = null;

        if (empty($search) || $search === 'list') {
            return Helper::text(__('twitch.help_available_list', ['url' => route('twitch.help') . '?list']));
        }

        $articles = HelpArticle::search($search)
                    ->select('id', 'title', 'published')
                    ->latest('published')
                    ->orderBy('title')
                    ->get();

        if ($articles->isEmpty()) {
            $msg = __('twitch.help_no_results');
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

        return Helper::text($title . ' - ' . $url);
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
                return Helper::text(__('generic.channel_name_required'));
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

        $highlights = $this->api->channelVideos($channel, 'highlight');

        if (empty($highlights)) {
            return Helper::text(__('twitch.no_highlights', [
                'channel' => ($channelName ?: $channel)
            ]));
        }

        $highlight = $highlights[0];
        $title = $highlight['title'];
        $url = $highlight['url'];
        return Helper::text(sprintf('%s - %s', $title, $url));
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
        $id = $request->input('id', 'false');

        if (empty($channel)) {
            $nb = new Nightbot($request);
            if (empty($nb->channel)) {
                return Helper::text(__('generic.channel_name_required'));
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

        $highlights = $this->api->channelVideos($channel, 'highlight', $limit);

        if (empty($highlights)) {
            return Helper::text(__('twitch.no_highlights', ['channel' => ($channelName ?: $channel)]));
        }

        $random = array_rand($highlights);
        $video = $highlights[$random];

        $title = $video['title'];
        $url = $video['url'];

        return Helper::text(sprintf('%s - %s', $title, $url));
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
        $user = $user ?? $request->input('user', null);

        if (empty($user)) {
            return Helper::text(__('generic.username_required'));
        }

        try {
            $data = $this->api->userByUsername($user);
        }
        catch (TwitchApiException $ex)
        {
            return Helper::text('Invalid Twitch username specified: ' . $user, 400);
        }

        if (empty($data)) {
            return Helper::text(__('twitch.user_not_found', [
                'user' => $user,
            ]));
        }

        return Helper::text($data['id']);
    }

    /**
     * Returns list of ingest servers, plus their templates and availabilities.
     *
     * @return Response
     */
    public function ingests()
    {
        /**
         * Use new ingests API
         */
        $ingests = Helper::get('https://ingest.twitch.tv/ingests');

        if (empty($ingests['ingests'])) {
            return $this->error(__('generic.error_loading_data'));
        }

        /**
         * Sort the ingest servers by the location name
         */
        usort($ingests['ingests'], function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        $info = '';
        $servers = $ingests['ingests'];
        foreach ($servers as $server) {
            $info .= sprintf('Name: %s%s', $server['name'], PHP_EOL);
            $info .= sprintf('    Template: %s%s', $server['url_template'], PHP_EOL);
            $info .= sprintf('    Availability: %.1f%s%s', $server['availability'], PHP_EOL, PHP_EOL);
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
        $service = strtolower($request->input('service', 'multistream'));
        $streams = $streams ?: $request->input('streams', null);

        $services = [
            'multitwitch' => [
                'link' => 'http://multitwitch.tv'
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
            return Helper::text(__('twitch.multi_invalid_service', [
                'services' => implode(', ', array_keys($services)),
            ]));
        }

        if (empty($streams)) {
            return Helper::text(__('twitch.multi_empty_list'));
        }

        $service = $services[$service];
        $streams = explode(' ', $streams);
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
     * Twitch Helix API doesn't include any form of timestamps
     * Therefore the API has been deprecated.
     */
    public function latestSub(Request $request)
    {
        $status = 410;

        /**
         * Return status code 200 for Nightbot requests
         * Since Nightbot responds with a generic error message on non-2xx.
         */
        $nb = new Nightbot($request);
        if (!empty($nb->channel)) {
            $status = 200;
        }

        return Helper::text('410 Gone - Not supported by the new Twitch API', $status);
    }

    /**
     * Get a random / latest subscriber from the channel that belongs to the OAuth token if provided, otherwise the specified channel
     *
     * @param  Request $request
     * @param  string  $channel
     * @return Response
     */
    public function randomSub(Request $request, $channel = null)
    {
        if ($request->exists('logout')) {
            return redirect()->route('auth.twitch.logout');
        }

        $id = false;
        $channel = $channel ?: $request->input('channel', null);
        $amount = intval($request->input('count', 1));

        // Fallback to 1
        if ($amount < 1) {
            $amount = 1;
        }

        $field = $request->input('field', 'user_name');
        $separator = $request->input('separator', ', ');
        $needToReAuth = '';
        $action = 'random';

        if(empty($channel) && Auth::check()) {
            $user = Auth::user();
            $userData = $this->api->userById($user->id);

            $data = [
                'page' => ucfirst($action) . ' subscriber',
                'route' => route('twitch.' . $action . '_sub'),
                'action' => $action,
                'channel' => $userData['login'],
            ];

            return view('twitch.random-sub', $data);
        }

        if (empty($channel)) {
            $nb = new Nightbot($request);
            if (empty($nb->channel)) {
                return Helper::text(__('generic.user_channel_name_required'));
            }
            $channel = $nb->channel['providerId'];
            $id = true;
        }

        $channel = trim($channel);
        $reAuth = route('auth.twitch.base') . sprintf('?redirect=randomsub&scopes=%s', $this->subScopes);
        $needToReAuth = sprintf(__('twitch.sub_needs_authentication'), $id === true ? $nb->channel['displayName'] : $channel, $action, $action, $reAuth);

        try {
            $channel = $id === true ? User::where('id', $channel)->first() : $this->userByName($channel)->user;
        } catch (Exception $e) {
            return Helper::text('An error occurred when trying to find channel or user.');
        }

        if (empty($channel)) {
            return Helper::text($needToReAuth);
        }

        try {
            $token = Crypt::decrypt($channel->access_token);
        } catch (DecryptException $e) {
            // Something weird happened with the encrypted token
            // request channel owner to re-auth so it's encrypted properly
            return Helper::text($needToReAuth);
        }

        if (empty($token)) {
            return Helper::text($needToReAuth);
        }

        $scopes = explode('+', $channel->scopes);

        if (!in_array('channel:read:subscriptions', $scopes)) {
            return Helper::text(__('twitch.auth_missing_scopes') . ' channel:read:subscriptions. ' . $needToReAuth);
        }

        $limit = 100;
        $userId = $channel->id;

        try {
            $this->api->setToken($token);
            $data = $this->api->subscriptions($userId, null, $limit);
        }
        catch (TwitchApiException $ex)
        {
            return Helper::text('[Error from Twitch API] ' . $ex->getMessage());
        }
        catch (Exception $ex)
        {
            return Helper::text(__('twitch.error_loading_data_api'));
        }

        $count = $data['count'];

        // I don't remember exactly why I did this, because a `count` can be considered a "max count".
        // if ($amount > $count) {
        //     return Helper::text(sprintf(__('twitch.sub_count_too_high'), $amount, $count));
        // }

        $subscriptions = $data['subscriptions']->resolve();

        // Request all subscriptions if the first batch didn't include all of them.
        if ($count > $limit && $count > $limit) {
            $subscriptions = $this->api->subscriptionsAll($userId);
        }

        $output = [];
        // Remove the broadcaster from the list
        $subscriptions = array_filter(
            $subscriptions,
            function ($subscription) use ($userId) {
                return $subscription['user_id'] !== $userId;
            }
        );

        shuffle($subscriptions);

        $output = array_map(
            function ($user) use ($field) {
                return $user[$field];
            },
            array_slice($subscriptions, 0, $amount)
        );

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
            return Helper::text(__('generic.channel_name_required'));
        }

        // Specific _users_ to exclude.
        $exclude = $request->input('exclude', '');
        $exclude = array_map('trim', explode(',', $exclude));

        // "Groups" of chatters to ignore.
        $ignore = $request->input('ignore', '');
        $ignore = array_map('trim', explode(',', $ignore));

        $data = $this->twitchApi->get('https://tmi.twitch.tv/group/user/' . $channel . '/chatters', true);

        if (empty($data) || empty($data['chatters'])) {
            return Helper::text(__('twitch.error_retrieving_chat_users') . $channel);
        }

        $users = [];
        foreach ($data['chatters'] as $group => $chatters) {
            if (!in_array($group, $ignore)) {
                $users = array_merge($users, $chatters);
            }
        }

        if (empty($users)) {
            return Helper::text(__('twitch.empty_chat_user_list'));
        }

        foreach ($exclude as $user) {
            $user = strtolower($user);
            $search = array_search($user, $users);

            if ($search === false) {
                continue;
            }

            unset($users[$search]);
        }

        if (empty($users)) {
            return Helper::text(__('twitch.empty_chat_user_list'));
        }

        shuffle($users);
        $rand = mt_rand(0, count($users) - 1);
        return Helper::text($users[$rand]);
    }

    /**
     * Twitch Helix API doesn't include any form of timestamps
     * Therefore the API has been deprecated.
     */
    public function subAge(Request $request, $channel = null, $user = null)
    {
        $status = 410;

        /**
         * Return status code 200 for Nightbot requests
         * Since Nightbot responds with a generic error message on non-2xx.
         */
        $nb = new Nightbot($request);
        if (!empty($nb->channel)) {
            $status = 200;
        }

        return Helper::text('410 Gone - Not supported by the new Twitch API', $status);
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

        if ($request->exists('logout')) {
            return redirect()->route('auth.twitch.logout');
        }

        if (empty($channel) && Auth::check()) {
            $user = Auth::user();
            $userData = $this->api->userById($user->id);
            $name = $userData['login'];
            $displayName = $userData['display_name'] ?: $name;

            $data = [
                'page' => 'Subcount',
                'route' => route('twitch.subcount', ['subcount' => 'subcount']),
                'name' => $displayName,
            ];

            return view('twitch.subcount', $data);
        }

        if (empty($channel)) {
            return Helper::text(__('twitch.subcount_missing_channel'));
        }

        $subtract = intval($request->input('subtract', 0));
        $id = $request->input('id', 'false');
        $channel = strtolower(trim($channel));
        $reAuth = route('auth.twitch.base') . '?redirect=subcount&scopes=' . $this->subScopes;
        $needToReAuth = sprintf(__('twitch.subcount_needs_authentication'), $channel, $reAuth);

        try {
            $user = $id === 'true' ? User::where('id', $channel)->first() : $this->userByName($channel)->user;
        } catch (Exception $e) {
            $field = $id === 'true' ? 'ID' : 'username';
            return Helper::text('An error occurred when trying to find a channel with the ' . $field . ': ' . $channel);
        }

        if (empty($user)) {
            return Helper::text($needToReAuth);
        }

        $scopes = explode('+', $user->scopes);
        if (!in_array('channel:read:subscriptions', $scopes)) {
            $needToReAuth .= '+' . implode('+', $scopes);
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

        /**
         * Retrieve subscriber data from Helix API,
         * including subscriber count.
         *
         * This will return cached data if available.
         */
        $userId = $user->id;
        try {
            $this->api->setToken($token);
            $subs = $this->api->subscriptionsMeta($userId);
        }
        catch (TwitchApiException $ex)
        {
            /**
             * OAuth token invalid, so we delete it from the table.
             */
            if ($ex->getCode() === 401) {
                $user->delete();
                return Helper::text($needToReAuth);
            }

            return Helper::text('[Error from Twitch API] ' . $ex->getMessage());
        }
        catch (Exception $ex)
        {
            return Helper::text(__('twitch.subcount_generic_error', [
                'channel' => $channel,
            ]));
        }

        /**
         * In some scenarios this returns `null` and I'm not entirely sure why yet.
         */
        if (empty($subs)) {
            return Helper::text(__('twitch.subcount_generic_error', [
                'channel' => $channel,
            ]));
        }

        $subcount = $subs['count'];
        return Helper::text($subcount - $subtract);
    }

    /**
     * Similar to "subcount", this retrieves the "subscriber points" used to calculate
     * when a partner receives a new emote slot.
     *
     * @param  Request $request
     * @param  string  $channel Channel name/ID
     * @return Response
     */
    public function subpoints(Request $request, $channel = null)
    {
        if (empty($channel) && Auth::check()) {
            $user = Auth::user();
            $userData = $this->api->userById($user->id);
            $name = $userData['login'];
            $displayName = $userData['display_name'] ?: $name;

            return view('twitch.subpoints', [
                'page' => 'Subpoints',
                'route' => route('twitch.subpoints', [
                    'channel' => $name,
                ]),
                'name' => $displayName,
            ]);
        }

        if (empty($channel)) {
            return Helper::text(__('twitch.subpoints_missing_channel'));
        }

        $id = $request->input('id', 'false');
        $channel = strtolower(trim($channel));
        $reAuth = route('auth.twitch.base') . '?redirect=subpoints&scopes=' . $this->subScopes;
        $needToReAuth = sprintf(__('twitch.subpoints_needs_authentication'), $channel, $reAuth);

        try {
            $user = $id === 'true' ? User::where('id', $channel)->first() : $this->userByName($channel)->user;
        } catch (Exception $e) {
            $field = $id === 'true' ? 'ID' : 'username';
            Log::error($e->getMessage());
            return Helper::text('An error occurred when trying to find a channel with the ' . $field . ': ' . $channel);
        }

        if (empty($user)) {
            return Helper::text($needToReAuth);
        }

        $scopes = explode('+', $user->scopes);
        if (!in_array('channel:read:subscriptions', $scopes)) {
            $needToReAuth .= '+' . implode('+', $scopes);
            return Helper::text($needToReAuth);
        }

        /**
         * Retrieve encrypted OAuth token from DB and attempt to decrypt.
         */
        try {
            $token = Crypt::decrypt($user->access_token);
        } catch (DecryptException $e) {
            Log::error($e->getMessage());
            return Helper::text($reAuth);
        }

        /**
         * Use OAuth token in Helix API requests and retrieve
         * all subscribers for the specified channel
         */
        try {
            $this->api->setToken($token);
            $subs = $this->api->subscriptionsMeta($user->id);
        }
        catch (TwitchApiException $ex)
        {
            /**
             * OAuth token invalid, so we delete it from the table.
             */
            if ($ex->getCode() === 401) {
                $user->delete();
                return Helper::text($needToReAuth);
            }

            return Helper::text('[Error from Twitch API] ' . $ex->getMessage());
        }
        catch (Exception $ex)
        {
            return Helper::text(__('twitch.subpoints_generic_error', [
                'channel' => $channel,
            ]));
        }

        /**
         * In some scenarios this returns `null` and I'm not entirely sure why yet.
         */
        if (empty($subs)) {
            return Helper::text(__('twitch.subpoints_generic_error', [
                'channel' => $channel,
            ]));
        }

        $subpoints = $subs['points'];
        return Helper::text($subpoints);
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
        $wantsPlans = ($request->exists('tiers') && $wantsJson);
        $id = $request->input('id', 'false');

        if (empty($channel)) {
            $message = __('generic.channel_name_required');
            if ($wantsJson) {
                return $this->errorJson(['message' => $message, 'status' => 404], 404);
            }

            $nb = new Nightbot($request);
            if (empty($nb->channel)) {
                return Helper::text($message);
            }

            $channel = $nb->channel['providerId'];
            $id = 'true';
        }

        if ($id !== 'true') {
            try {
                $user = $this->api->userByUsername($channel);
            }
            catch (TwitchApiException $ex)
            {
                return Helper::text('Invalid Twitch user specified: ' . $channel, 400);
            }
            catch (Exception $ex)
            {
                Log::error($ex->getMessage());
                return Helper::text('Error occurred retrieving user information for Twitch user: ' . $channel);
            }

            // API returned an empty response, most likely disabled/banned user or it doesn't exist.
            if (empty($user)) {
                return Helper::text(__('twitch.user_not_found', ['user' => $channel]));
            }

            if (!empty($user['message'])) {
                return Helper::text($user['message']);
            }

            $channel = $user['id'];
        }

        try {
            $emotes = $this->api->channelEmotesById($channel);

            /**
             * We only care about subscriber emotes for this API endpoint.
             */
            $emotes = array_filter($emotes, function($emote) {
                return $emote['type'] === 'subscriptions';
            });
        }
        catch (TwitchApiException $ex) {
            if ($wantsJson) {
                return $this->errorJson([
                    'error' => 'API error',
                    'message' => $ex->getMessage(),
                    'status' => 500,
                ], 500);
            }

            return Helper::text('[Twitch API Error] ' . $ex->getMessage());
        }
        catch (Exception $ex)
        {
            if ($wantsJson) {
                return $this->errorJson([
                    'error' => 'API error',
                    'message' => 'Error loading the requested data.',
                    'status' => 500,
                ], 500);
            }

            return Helper::text(__('generic.error_loading_data_api'));
        }

        if (empty($emotes)) {
            $message = __('twitch.channel_missing_subemotes');
            if ($wantsJson) {
                return $this->errorJson(['message' => $message], 404);
            }

            return Helper::text($message);
        }

        /**
         * Changes were made this setup pretty scuffed when migrating to Twitch's Helix API.
         *
         * Ideally I'd wanna move most of the logic in terms of filtering and sorting
         * into the collection and make the repository function return the collection.
         *
         * But right now I'm too lazy to think about a clever way, so this is what I'm doing.
         */
        if ($wantsPlans) {
            /**
             * Map the tiers from the Twitch API
             */
            $tierMapping = [
                '1000' => 'tier1',
                '2000' => 'tier2',
                '3000' => 'tier3',
            ];

            /**
             * Create an 'empty skeleton' for the response.
             */
            $emoteData = [
                'emotes' => [
                    'tier1' => [],
                    'tier2' => [],
                    'tier3' => [],
                ],
            ];

            /**
             * Go through each emote and add it to the response based on the tier.
             */
            foreach ($emotes as $emote)
            {
                $tier = $emote['tier'];

                if (!isset($tierMapping[$tier])) {
                    continue;
                }

                $tierName = $tierMapping[$tier];
                $emoteData['emotes'][$tierName][] = $emote['code'];
            }

            return $this->json($emoteData);
        }

        /**
         * At this point we only want the emote codes.
         *
         * `array_values` is used because it seems that sometimes it returns an array with missing keys.
         * When JSON-encoding it, it turns it into an object.
         */
        $emoteCodes = array_values(
            array_map(
                function($emote) {
                    return $emote['code'];
                },
                $emotes
            )
        );

        if ($wantsJson) {
            return $this->json([
                'emotes' => $emoteCodes,
            ]);
        }

        return Helper::text(implode(' ', $emoteCodes));
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
            $message = __('twitch.teams_missing_identifier');

            if ($wantsJson) {
                return Helper::json(['message' => $message, 'status' => 404], 404);
            }

            return Helper::text($message);
        }

        try {
            $team = $this->api->teamByName($team);
        }
        catch (TwitchApiException $ex) {
            return Helper::text('[Error from Twitch API] ' . $ex->getMessage());
        }
        catch (Exception $ex)
        {
            Log::error($ex->getMessage());
            return Helper::text('Error occurred retrieving team information for Twitch team: ' . $team);
        }

        $users = $team['users'];
        $members = [];
        foreach ($users as $user) {
            $members[] = (in_array('display_names', $settings) ? $user['user_name'] : $user['user_login']);
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

        $channel = $channel ?? $request->input('channel', null);
        if (empty($channel)) {
            $nb = new Nightbot($request);
            if (empty($nb->channel)) {
                return Helper::text(__('generic.channel_name_required'));
            }

            $channel = $nb->channel['providerId'];
            $id = 'true';
        }

        try {
            $data = $id === 'true' ? $this->api->userById($channel) : $this->api->userByUsername($channel);
        }
        catch (TwitchApiException $ex)
        {
            return Helper::text('Invalid Twitch user specified: ' . $channel, 400);
        }
        catch (Exception $ex)
        {
            Log::error($ex->getMessage());
            return Helper::text('Error occurred retrieving user information for Twitch user: ' . $channel);
        }

        if (empty($data)) {
            return Helper::text(__('twitch.user_not_found', [
                'user' => $channel,
            ]));
        }

        return Helper::text($data['view_count']);
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
                return Helper::text(__('generic.channel_name_required'));
            }

            $channel = $nb->channel['providerId'];
            $id = 'true';
        }

        /**
         * streamByName()/streamById() caches for up to 2 minutes (defined in config/twitch.php)
         */
        try {
            if ($id === 'true') {
                $streams = $this->api->streamById($channel);
            }
            else {
                $streams = $this->api->streamByName($channel);
            }
        }
        catch (TwitchApiException $ex)
        {
            return Helper::text('[Error from Twitch API] ' . $ex->getMessage());
        }
        catch (Exception $ex)
        {
            return Helper::text(__('twitch.stream_get_error', [
                'channel' => $channel,
            ]));
        }

        $defaultOffline = __('twitch.stream_offline', ['channel' => $channel]);
        $offline = $request->input('offline_msg', $defaultOffline);

        if (empty($streams['streams'])) {
            return Helper::text($offline);
        }

        $stream = $streams['streams'][0];
        $start = $stream['created_at'];
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
                return Helper::text(__('generic.channel_name_required'));
            }

            $channel = $nb->channel['providerId'];
            $id = 'true';
        }

        /**
         * streamByName()/streamById() caches for up to 2 minutes (defined in config/twitch.php)
         */
        try {
            if ($id === 'true') {
                $streams = $this->api->streamById($channel);
            }
            else {
                $streams = $this->api->streamByName($channel);
            }
        }
        catch (TwitchApiException $ex)
        {
            return Helper::text('[Error from Twitch API] ' . $ex->getMessage());
        }
        catch (Exception $ex)
        {
            return Helper::text(__('twitch.stream_get_error', [
                'channel' => $channel,
            ]));
        }

        $stream = $streams['streams'][0] ?? [];

        if (empty($stream)) {
            $offline = __('twitch.stream_offline', ['channel' => $channel]);
            return Helper::text($offline);
        }

        $viewers = $stream['viewers'];
        return Helper::text($viewers);
    }

    /**
     * Returns a video list (by default only "VODs" also known as archives) for the specified channel.
     *
     * @param Request $request
     * @param string $channel
     * @return void
     */
    public function videos(Request $request, $channel = null)
    {
        $id = $request->input('id', 'false');

        if (empty($channel)) {
            $nb = new Nightbot($request);
            if (empty($nb->channel)) {
                return Helper::text(__('generic.channel_name_required'));
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

        $limit = intval($request->input('limit', 1));
        $broadcastTypes = $request->input('broadcast_type', 'archive');
        $separator = $request->input('separator', ' | ');
        $format = $request->input('video_format', '${title} - ${url}');

        if ($limit > 100 || $limit < 1) {
            $limitError = __('twitch.invalid_limit_parameter', [
                'min' => '1',
                'max' => '100',
            ]);

            return Helper::text($limitError);
        }

        $formattedVideos = [];
        try {
            $videos = $this->api->channelVideos($channel, $broadcastTypes, $limit);

            foreach ($videos as $video) {
                $formattedVideos[] = str_replace(['${title}', '${url}'], [$video['title'], $video['url']], $format);
            }
        }
        catch (TwitchApiException $ex)
        {
            return Helper::text('[Error from Twitch API] ' . $ex->getMessage());
        }
        catch (Exception $ex)
        {
            return Helper::text(__('twitch.error_loading_data_api'));
        }

        if (count($formattedVideos) === 0) {
            return Helper::text(__('twitch.end_of_video_list'));
        }

        return Helper::text(implode($separator, $formattedVideos));
    }

    /**
     * Retrieves the latest broadcast (VOD), takes the current date and subtracts a specified amount of time.
     * Takes the result and returns a formatted URL to a specific timestamp in the VOD.
     *
     * @param Request $request
     * @param string $channel
     * @return void
     */
    public function vodReplay(Request $request, $channel = null)
    {
        $id = $request->input('id', 'false');

        if (empty($channel)) {
            $nb = new Nightbot($request);
            if (empty($nb->channel)) {
                return Helper::text(__('generic.channel_name_required'));
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

        // The amount of minutes to go back in the VOD.
        $minutes = intval($request->input('minutes', 5));
        if ($minutes < 0) {
            return Helper::text(__('twitch.invalid_minutes_parameter', [
                'min' => $minutes,
            ]));
        }

        try {
            $videos = $this->api->channelVideos($channel, 'archive', 1);
        }
        catch (TwitchApiException $ex)
        {
            return Helper::text('[Error from Twitch API] ' . $ex->getMessage());
        }
        catch (Exception $ex)
        {
            return Helper::text(__('twitch.error_loading_data_api'));
        }

        $vod = $videos[0] ?? [];
        if (empty($vod)) {
            return Helper::text(__('twitch.no_vods'));
        }

        $interval = CarbonInterval::fromString($vod['duration']);
        $totalSeconds = $interval->totalSeconds;

        if (($minutes * 60) > $totalSeconds) {
            return Helper::text(__('twitch.vodreplay_minutes_too_high', [
                'min' => $minutes,
            ]));
        }

        $vodStart = Carbon::parse($vod['created_at']);
        $vodEnd = $vodStart
                  ->copy()
                  ->addSeconds($totalSeconds);

        $difference = $vodStart->diffAsCarbonInterval($vodEnd->subMinutes($minutes));

        $hours = ($difference->hours + ($difference->dayz * 24));
        $url = sprintf('%s?t=%dh%dm%ds', $vod['url'], $hours, $difference->minutes, $difference->seconds);
        return Helper::text($url);
    }
}
