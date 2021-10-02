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
    private $subScopes = 'user_read+channel:read:subscriptions+user:read:email';

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
     * The 'Accept' header to receive Twitch API V5 responses.
     *
     * @var array
     */
    private $version = ['Accept' => 'application/vnd.twitchtv.v5+json'];

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
     * TODO: Move this to a proper location, same with `userByName()`.
     * Retrieves usernames by their IDs.
     *
     * First it checks the local database cache (`CachedTwitchUser`).
     * Any IDs that haven't been cached is then queued up for querying the API,
     * in batches of 100 IDs per request. Results are then cached for next time.
     *
     * @param array $ids Array of Twitch user IDs
     * @return App\CachedTwitchUser Collection of CachedTwitchUsers instances.
     */
    private function usernamesByIds($ids = [])
    {
        $cachedUsers = CachedTwitchUser
                        ::whereIn('id', $ids)
                        ->get();

        /**
         * Filter away IDs that have already been retrieved
         * from cached users.
         */
        $cachedUserIds = $cachedUsers
                            ->pluck('id')
                            ->toArray();
        $checkIds = array_diff($ids, $cachedUserIds);

        /**
         * Take the missing IDs, split them into 100 chunks
         * Use said chunks to request user information.
         */
        $idChunks = array_chunk($checkIds, 100);
        foreach ($idChunks as $chunk)
        {
            /**
             * Retrieve information about all the user IDs
             */
            $users = $this->api->usersByIds($chunk);

            /**
             * Cache user IDs and add it to the original `cachedUsers`.
             * Next time most users should be loaded from database.
             */
            foreach ($users as $user)
            {
                $cacheUser = new CachedTwitchUser([
                    'id' => $user['id'],
                    'username' => $user['login'],
                ]);

                $cacheUser->save();

                $cachedUsers->push($cacheUser);
            }
        }

        return $cachedUsers;
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
            'subage' => 'subage/{CHANNEL}/{USER}',
            'subcount' => 'subcount/{CHANNEL}',
            'subpoints' => 'subpoints/{CHANNEL}',
            'subscriber_emotes' => 'subscriber_emotes/{CHANNEL}',
            'subage' => 'subage/{CHANNEL}/{USER}',
            'status' => 'status/{CHANNEL}',
            'title' => 'title/{CHANNEL}',
            'team_members' => 'team_members/{TEAM_ID}',
            'total_views' => 'total_views/{CHANNEL}',
            'upload' => 'upload/{CHANNEL}',
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
                    $user = $this->api->userByUsername($user);
                }
                else {
                    $user = $this->api->userById($user);
                }

                $timestamp = $user['created_at'];
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
                    $user = $this->api->userByUsername($user);
                }
                else {
                    $user = $this->api->userById($user);
                }

                $timestamp = $user['created_at'];
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

        if ($id !== 'true') {
            try {
                /**
                 * Save username/channel name in separate variables before
                 * overwriting them to use in later calls.
                 */
                $username = $user;
                $channelName = $channel;

                $channel = $this->userByName($channel)->id;
                $user = $this->userByName($user)->id;
            } catch (Exception $e) {
                return Helper::text($e->getMessage());
            }
        }

        $getFollow = $this->twitchApi->followRelationship($user, $channel, $this->version);
        $status = $getFollow['status'] ?? null;
        if (!empty($status)) {
            /**
             * 404 = Follow not found, when :user isn't following :channel.
             */
            if ($status === 404) {
                return Helper::text(__('twitch.follow_not_found', [
                    'user' => $username ?? $user,
                    'channel' => $channelName ?? $channel,
                ]));
            }

            return Helper::text($getFollow['message']);
        }

        return Helper::text(Helper::getDateDiff($getFollow['created_at'], time(), $precision));
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

        try {
            $getFollowers = $this->api->followsChannel($channel);
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

        return Helper::text($getFollowers['total']);
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

        if ($id !== 'true') {
            try {
                /**
                 * Set different variables for error messages (usernames instead of IDs).
                 */
                $channelName = $channel;
                $userName = $user;

                $channel = $this->userByName($channel)->id;
                $user = $this->userByName($user)->id;
            } catch (Exception $e) {
                return Helper::text($e->getMessage());
            }
        }

        try {
            $getFollow = $this->api->followRelationship($channel, $user);
        }
        catch (TwitchApiException $ex)
        {
            return Helper::text('[Error from Twitch API] ' . $ex->getMessage());
        }
        catch (Exception $ex)
        {
            return Helper::text(__('twitch.unable_get_following'));
        }

        /**
         * Information from API was valid, but empty.
         */
        if (empty($getFollow)) {
            return Helper::text(__('twitch.follow_not_found', [
                'user' => $userName ?? $user,
                'channel' => $channelName ?? $channel,
            ]));
        }

        $follow = $getFollow[0];
        $time = Carbon::parse($follow['followed_at']);
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
        $cursor = $request->input('cursor', '');
        $direction = $request->input('direction', 'desc');
        $showNumbers = ($request->exists('num') || $request->exists('show_num')) ? true : false;
        $separator = $request->input('separator', ', ');
        $useUsernames = $request->exists('username');

        // Fields inside the `user` object that will be returned in the JSON response.
        // See: https://dev.twitch.tv/docs/v5/reference/channels/#get-channel-followers for reference
        // `created_at` in the root object for each follow is always included.
        $inputFields = $request->input('fields', 'name,_id');

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

        if ($count > 100) {
            if ($request->wantsJson()) {
                return Helper::json(['error' => __('generic.max_count', ['value' => 100])], 400);
            }

            return Helper::text(__('generic.max_count', ['value' => 100]));
        }

        $followers = $this->twitchApi->channelFollows($channel, $count, $offset, $direction, $this->version, $cursor);

        if (!empty($followers['status'])) {
            if ($request->wantsJson()) {
                return Helper::json($followers, $followers['status']);
            }

            return Helper::text($followers['message']);
        }

        if (!isset($followers['follows'])) {
            if ($request->wantsJson()) {
                return Helper::json(['error' => __('twitch.error_followers'), 500]);
            }

            return Helper::text(__('twitch.error_followers'));
        }

        $follows = $followers['follows'];

        if (count($follows) === 0) {
            if ($request->wantsJson()) {
                return Helper::json([
                    'cursor' => null,
                    'total' => $followers['_total'],
                    'followers' => [],
                ]);
            }

            return Helper::text(__('twitch.no_followers'));
        }

        $users = [];
        if ($request->wantsJson()) {
            $fields = array_map('trim', explode(',', $inputFields));
            $availableFields = array_keys($follows[0]['user']);
            $validFields = array_filter($fields, function ($field) use ($availableFields) {
                return in_array($field, $availableFields);
            });

            foreach ($follows as $follow) {
                $currentFollow = [
                    'follow_created' => $follow['created_at'],
                ];

                foreach ($validFields as $field) {
                    $currentFollow[$field] = $follow['user'][$field];
                }

                $users[] = $currentFollow;
            }

            return Helper::json([
                'cursor' => (isset($followers['_cursor']) ? $followers['_cursor'] : null),
                'total' => $followers['_total'],
                'followers' => $users,
            ]);
        }

        $currentNumber = 0;
        foreach ($follows as $user) {
            $user = $user['user'];
            $name = $user['name'];

            if (!$useUsernames && !empty($user['display_name'])) {
                $name = $user['display_name'];
            }

            $currentNumber++;
            $users[] = ($showNumbers ? $currentNumber . '. ' : '') . $name;
        }

        return Helper::text(implode($separator, $users));
    }

    /**
     * Returns a list of the channels a user is following.
     *
     * @param Request $request
     * @param string $user
     * @return void
     */
    public function following(Request $request, $user = null)
    {
        $id = $request->input('id', 'false');
        if ($id !== 'true') {
            try {
                // Store channel name separately for potential messages and override $channel
                $username = $user;
                $user = $this->userByName($user)->id;
            } catch (Exception $e) {
                return Helper::text($e->getMessage());
            }
        }

        $direction = $request->input('direction', 'desc');
        $limit = intval($request->input('limit', 25));
        $offset = intval($request->input('offset', 0));
        $separator = $request->input('separator', ', ');

        // Fields inside the `channels` object that will be returned in the JSON response.
        // See: https://dev.twitch.tv/docs/v5/reference/users/#get-user-follows for reference
        // `created_at` in the root object is always included.
        $inputFields = $request->input('fields', 'name,_id');

        // Similar to $inputFields, except this is the single field used from the
        // `channel` object whenever a text response is returned (default).
        $textField = $request->input('field', 'name');

        if ($limit < 0 || $limit > 100) {
            $errorText = __('generic.invalid_limit', ['limit' => $limit]);
            if ($request->wantsJson()) {
                return Helper::json(['error' => $errorText], 400);
            }

            return Helper::text($errorText);
        }

        if ($offset < 0) {
            $errorText = __('generic.invalid_offset', ['offset' => $offset]);
            if ($request->wantsJson()) {
                return Helper::json(['error' => $errorText], 400);
            }

            return Helper::text($errorText);
        }

        $channels = $this->twitchApi->userFollowsChannels($user, $limit, $offset, $direction, $this->version);

        if (isset($channels['error'])) {
            if ($request->wantsJson()) {
                return Helper::json($channels, $channels['status']);
            }

            return Helper::text($channels['error'] . ' - ' . $channels['message']);
        }

        $follows = $channels['follows'];

        if (!is_array($follows)) {
            Log:error(sprintf('/twitch/following: `Follows` key for user %s invalid. Array expected, got: %s', $user, gettype($follows)));

            if ($request->wantsJson()) {
                return Helper::json([
                    'error' => __('twitch.invalid_api_data'),
                    'status' => 500,
                ], 500);
            }

            return Helper::text(__('twitch.unable_get_following'));
        }

        if (count($follows) === 0) {
            if ($request->wantsJson()) {
                return Helper::json($follows);
            }

            return Helper::text(__('twitch.end_following_list'));
        }

        $list = [];
        if ($request->wantsJson()) {
            $fields = array_map('trim', explode(',', $inputFields));
            $availableFields = array_keys($follows[0]['channel']);
            $validFields = array_filter($fields, function ($field) use ($availableFields) {
                return in_array($field, $availableFields);
            });

            foreach ($follows as $follow) {
                $currentFollow = [
                    'follow_created' => $follow['created_at'],
                ];

                foreach ($validFields as $field) {
                    $currentFollow[$field] = $follow['channel'][$field];
                }

                $list[] = $currentFollow;
            }

            return Helper::json($list);
        }

        foreach ($follows as $follow) {
            $list[] = $follow['channel'][$textField];
        }

        return Helper::text(implode($separator, $list));
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

        $fetchHighlight = $this->twitchApi->videos($request, $channel, ['highlight'], 1, 0, $this->version);

        if (!empty($fetchHighlight['status'])) {
            return Helper::text($fetchHighlight['message']);
        }

        if (empty($fetchHighlight['videos'])) {
            return Helper::text(__('twitch.no_highlights', [
                'channel' => ($channelName ?: $channel)
            ]));
        }

        $highlight = $fetchHighlight['videos'][0];
        $title = $highlight['title'];
        $url = $highlight['url'];
        return Helper::text($title . ' - ' . $url);
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

        $data = $this->twitchApi->videos($request, $channel, ['highlight'], $limit, $offset, $this->version);

        if (!empty($data['status'])) {
            return Helper::text($data['message'], $data['status']);
        }

        if (empty($data['videos'])) {
            return Helper::text(__('twitch.no_highlights', ['channel' => ($channelName ?: $channel)]));
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
        $translation = sprintf('410 Gone - %s', __('twitch.api_removed_by_twitch'));
        $status = 410;

        /**
         * Return status code 200 for Nightbot requests
         * Since Nightbot responds with a generic error message on non-2xx.
         */
        $nb = new Nightbot($request);
        if (!empty($nb->channel)) {
            $status = 200;
        }

        return Helper::text($translation, $status);
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
        return $this->hosts($request);
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
     * Get a random / latest subscriber from the channel that belongs to the OAuth token if provided, otherwise the specified channel
     *
     * @param  Request $request
     * @param  string  $channel
     * @return Response
     */
    public function subList(Request $request, $channel = null)
    {
        $actions = ['random', 'latest'];
        $action = isset($request->route()->getAction()['action']) ? $request->route()->getAction()['action'] : 'random';

        if (!in_array($action, $actions)) {
            return Helper::text(__('twitch.sub_invalid_action', [
                'actions' => implode(', ', $actions),
            ]));
        }

        if ($request->exists('logout')) {
            return redirect()->route('auth.twitch.logout');
        }

        $id = false;
        $channel = $channel ?: $request->input('channel', null);
        $token = $request->input('token', null);
        $amount = intval($request->input('count', 1));

        // Fallback to 1
        if ($amount < 1) {
            $amount = 1;
        }

        $field = $request->input('field', 'name');
        $separator = $request->input('separator', ', ');
        $needToReAuth = '';

        if (!empty($token)) {
            $tokenData = $this->twitchApi->base($token, $this->version)['token'];
            if ($tokenData['valid'] === false) {
                return Helper::text('The specified OAuth token is invalid.');
            }
        } elseif(empty($channel) && Auth::check()) {
            $user = Auth::user();
            $userData = $this->twitchApi->users($user->id, $this->version);

            $data = [
                'page' => ucfirst($action) . ' subscriber',
                'route' => route('twitch.' . $action . '_sub'),
                'action' => $action,
                'channel' => $userData['name']
            ];
            return view('twitch.sublist', $data);
        } else {
            if (empty($channel)) {
                $nb = new Nightbot($request);
                if (empty($nb->channel)) {
                    return Helper::text(__('generic.user_channel_name_required'));
                }
                $channel = $nb->channel['providerId'];
                $id = true;
            }

            $channel = trim($channel);
            $reAuth = route('auth.twitch.base') . sprintf('?redirect=%ssub&scopes=%s', $action, $this->subScopes);
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
            } else {
                $tokenData = $this->twitchApi->base($token, $this->version)['token'];
                if ($tokenData['valid'] === false) {
                    return Helper::text($needToReAuth);
                }
            }
        }

        if (!in_array('channel_subscriptions', $tokenData['authorization']['scopes'])) {
            return Helper::text(__('twitch.auth_missing_scopes') . ' channel_subscriptions. ' . $needToReAuth);
        }

        $limit = 100;
        $data = $this->twitchApi->channelSubscriptions($tokenData['user_id'], $token, $limit, 0, 'desc', $this->version);

        if (!empty($data['error'])) {
            return Helper::text(sprintf('%s - %s (%s)', __('generic.error_loading_data_api'), $data['error'], $data['message']));
        }

        $count = $data['_total'];

        if ($amount > $count) {
            return Helper::text(sprintf(__('twitch.sub_count_too_high'), $amount, $count));
        }

        $subscriptions = $data['subscriptions'];

        /**
         * Hotfix for Twitch API (Kraken V5) bug.
         *
         * Seems Kraken has an issue with sorting when direction=desc and limit > 1, though I'm not sure about exact params.
         * The result seems to be completely randomized, which is just silly.
         * We try to avoid that by sorting by `created_at`, but only for `latest`.
         * It kinda 'helps' making the "random sub" even more random, ironically.
         */
        if ($action === 'latest') {
            usort($subscriptions, function($a, $b) {
                $first = strtotime($a['created_at']);
                $second = strtotime($b['created_at']);

                return $first < $second;
            });
        }

        $output = [];

        if ($action == 'random') {
            $offset = 0;
            if ($count > $limit) {
                while ($offset < $count) {
                    $offset += 100;
                    $data = $this->twitchApi->channelSubscriptions($tokenData['user_id'], $token, $limit, $offset, 'desc', $this->version);
                    $subscriptions = array_merge($subscriptions, $data['subscriptions']);
                }
            }
            shuffle($subscriptions);

            for ($i = 0; $i < $amount; $i++) {
                $index = mt_rand(0, count($subscriptions) - 1);

                if (isset($subscriptions[$index]['user'][$field])) $output[] = $subscriptions[$index]['user'][$field];

                unset($subscriptions[$index]);
                shuffle($subscriptions); // Reset array keys
            }
        }
        elseif ($action == 'latest') {
            $subscriptions = array_slice($subscriptions, 0, $amount);
            foreach ($subscriptions as $subscription) {
                if (isset($subscription['user'][$field])) $output[] = $subscription['user'][$field];
            }
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
     * Returns the length a user has subscribed to a channel
     *
     * @param  Request $request
     * @param  string  $channel
     * @param  string  $user
     * @return Response
     */
    public function subAge(Request $request, $channel = null, $user = null)
    {
        $channel = $channel ?: $request->input('channel', null);
        $user = $user ?: $request->input('user', null);
        $id = $request->exists('id');

        $precision = intval($request->input('precision')) ? intval($request->input('precision')) : 2;

        if ($request->exists('logout')) {
            return redirect()->route('auth.twitch.logout');
        }

        if (empty($channel) && empty($user) && Auth::check()) {
            $user = Auth::user();
            $userData = $this->twitchApi->users($user->id, $this->version);
            $name = $userData['name'];

            $data = [
                'page' => 'Subscription Age',
                'route' => route('twitch.subage', ['channel' => $name])
            ];
            return view('twitch.subage', $data);
        }

        if (empty($channel) || empty($user)) {
            $nb = new Nightbot($request);
            if (empty($nb->channel) || empty($nb->user)) {
                return Helper::text(__('generic.user_channel_name_required'));
            }

            $channel = $channel ?: $nb->channel['providerId'];
            $user = $user ?: $nb->user['providerId'];
            $id = true;
        }

        $channel = trim($channel);
        $user = trim($user);

        $reAuth = route('auth.twitch.base') . '?redirect=subage&scopes=user_read+channel_check_subscription';
        $needToReAuth = sprintf(__('twitch.subage_needs_authentication'), $id === true ? $nb->channel['displayName'] : $channel, $reAuth);

        try {
            $channel = $id === true ? User::where('id', $channel)->first() : $this->userByName($channel)->user;
            if ($id === false) $user = $this->userByName($user)->id;
        } catch (Exception $e) {
            return Helper::text('An error occurred when trying to find channel or user.');
        }

        if (empty($channel)) {
            return Helper::text($needToReAuth);
        }

        $scopes = explode('+', $channel->scopes);

        if (!in_array('channel_check_subscription', $scopes)) {
            $needToReAuth .= '+' . implode('+', $scopes);
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
        } else {
            $tokenData = $this->twitchApi->base($token, $this->version)['token'];
            if ($tokenData['valid'] === false) {
                return Helper::text($needToReAuth);
            }
        }

        $getSub = $this->twitchApi->subscriptionRelationship($channel->id, $user, $token, $this->version);
        if (!empty($getSub['status'])) {
            return Helper::text($getSub['message']);
        }

        return Helper::text(Helper::getDateDiff($getSub['created_at'], time(), $precision));
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

            $data = [
                'page' => 'Subcount',
                'route' => route('twitch.subcount', ['subcount' => 'subcount', 'channel' => $name])
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
            return Helper::text('[Error from Twitch API] ' . $ex->getMessage());
        }
        catch (Exception $ex)
        {
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

            return view('twitch.subpoints', [
                'page' => 'Subpoints',
                'route' => route('twitch.subpoints', [
                    'channel' => $name,
                ]),
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
            return Helper::text('[Error from Twitch API] ' . $ex->getMessage());
        }
        catch (Exception $ex)
        {
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

        $video = $this->twitchApi->videos($request, $channel, ['upload'], 1, 0, $this->version);

        if (!empty($video['status'])) {
            return Helper::text($video['message']);
        }

        if (empty($video['videos'])) {
            return Helper::text(__('twitch.no_uploads', [
                'channel' => ($channelName ?: $channel),
            ]));
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
                return Helper::text(__('generic.channel_name_required'));
            }

            $channel = $nb->channel['providerId'];
            $id = 'true';
        }

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
        $channelName = null;

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
                // Store channel name separately for potential messages and override $channel
                $channelName = $channel;
                $channel = $this->userByName($channel)->id;
            } catch (Exception $e) {
                return Helper::text($e->getMessage());
            }
        }

        /**
         * Load viewercount from cache to prevent unneccessary API request.
         */
        $cacheKey = sprintf('twitch_viewercount_%s', md5($channel));
        if (Cache::has($cacheKey)) {
            return Helper::text(Cache::get($cacheKey));
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
        // Add viewercount to the cache and cache it for 60 seconds.
        Cache::put($cacheKey, $viewers, config('twitch.cache.viewercount'));
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
                // Store channel name separately for potential messages and override $channel
                $channelName = $channel;
                $channel = $this->userByName($channel)->id;
            } catch (Exception $e) {
                return Helper::text($e->getMessage());
            }
        }

        $offset = intval($request->input('offset', 0));
        $limit = intval($request->input('limit', 1));
        $broadcastTypes = $request->input('broadcast_type', 'archive');
        $separator = $request->input('separator', ' | ');
        $format = $request->input('video_format', '${title} - ${url}');

        if ($limit > 100 || $limit < 1) {
            $limitError = __('twitch.invalid_limit_parameter', [
                'min' => '1',
                'max' => '100',
            ]);

            if ($request->wantsJson()) {
                return Helper::json([
                    'error' => $limitError,
                    'status' => 400,
                ], 400);
            }

            return Helper::text($limitError);
        }

        if ($offset < 0) {
            $offsetError = __('twitch.invalid_offset_parameter', [
                'min' => '0',
            ]);

            if ($request->wantsJson()) {
                return Helper::json([
                    'error' => $offsetError,
                    'status' => 400,
                ], 400);
            }

            return Helper::text($offsetError);
        }

        $videos = $this->twitchApi->videos($request, $channel, explode(',', $broadcastTypes), $limit, $offset, $this->version);

        if (!empty($videos['status'])) {
            if ($request->wantsJson()) {
                return Helper::json($videos, $videos['status']);
            }

            return Helper::text($videos['status'] . ' - ' . $videos['message']);
        }

        if (!isset($videos['videos'])) {
            if ($request->wantsJson()) {
                return Helper::json([
                    'error' => __('generic.error_loading_data_api'),
                    'status' => 503,
                ], 503);
            }

            return Helper::text(__('generic.error_loading_data_api'));
        }

        $videoList = $videos['videos'];
        if (count($videoList) === 0) {
            if ($request->wantsJson()) {
                return Helper::json([
                    'total' => $videos['_total'],
                    'videos' => $videoList,
                ]);
            }

            return Helper::text(__('twitch.end_of_video_list'));
        }

        if ($request->wantsJson()) {
            return Helper::json([
                'total' => $videos['_total'],
                'videos' => $videoList,
            ]);
        }

        $formattedVideos = [];
        foreach ($videoList as $video) {
            $formattedVideos[] = str_replace(['${title}', '${url}'], [$video['title'], $video['url']], $format);
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
                // Store channel name separately for potential messages and override $channel
                $channelName = $channel;
                $channel = $this->userByName($channel)->id;
            } catch (Exception $e) {
                return Helper::text($e->getMessage());
            }
        }

        // The amount of minutes to go back in the VOD.
        $minutes = intval($request->input('minutes', 5));
        $offset = intval($request->input('offset', 0));

        if ($minutes < 1) {
            return Helper::text(__('twitch.invalid_minutes_parameter', [
                'min' => $minutes,
            ]));
        }

        $video = $this->twitchApi->videos($request, $channel, ['archive'], 1, $offset, $this->version);

        if (!empty($video['status'])) {
            return Helper::text($video['message']);
        }

        if (empty($video['videos'])) {
            return Helper::text(__('twitch.no_vods', [
                'channel' => ($channelName ?: $channel),
            ]));
        }

        $vod = $video['videos'][0];

        if (($minutes * 60) > $vod['length']) {
            return Helper::text(__('twitch.vodreplay_minutes_too_high', [
                'min' => $minutes,
            ]));
        }

        $vodStart = Carbon::parse($vod['created_at']);
        $vodEnd = $vodStart
                  ->copy()
                  ->addSeconds($vod['length']);

        $difference = $vodStart->diffAsCarbonInterval($vodEnd->subMinutes($minutes));

        $url = sprintf('%s?t=%dh%dm%ds', $vod['url'], $difference->hours, $difference->minutes, $difference->seconds);
        return Helper::text($url);
    }
}
