<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use GuzzleHttp\Client;
use Exception;
use Log;
use App\CachedTwitchUser as CachedUser;

class TwitchApiController extends Controller
{
    const API_BASE_URL = 'https://api.twitch.tv/kraken/';
    public $twitchClientID = '';
    private $twitchClientSecret = '';

    /**
     * Initiliazes the controller.
     *
     * @param string $clientId     Twitch client ID
     * @param string $clientSecret Twitch client Secret
     */
    public function __construct($clientId = null, $clientSecret = null)
    {
        $clientId = $clientId ?: env('TWITCH_CLIENT_ID', null);
        $clientSecret = $clientSecret ?: env('TWITCH_CLIENT_ID', null);
        $this->twitchClientID = $clientId;
        $this->twitchClientSecret = $clientSecret;
    }

    /**
     * Sends a standard GET request to the Twitch Kraken API, unless overridden.
     *
     * @param  string $url    Endpoint URL, such as '/streams/channel-name';
     * @param  bool   $override Overrides the call to call the $url parameter directly instead of appending the $url parameter to the Kraken API
     * @param  array  $header Array of HTTP headers to send with the request. Client ID is already included in each request.
     * @return array          JSON-decoded object of the result
     */
    public function get($url = '', $override = false, $headers = [])
    {
        $settings['headers'] = $headers;

        /**
         * Allow methods to override client ID header.
         */
        if (empty($settings['headers']['Client-ID'])) {
            $settings['headers']['Client-ID'] = $this->twitchClientID;
        }

        if (empty($settings['headers']['Accept'])) {
            $settings['headers']['Accept'] = 'application/vnd.twitchtv.v3+json';
        }

        $settings['http_errors'] = false;
        $client = new Client();
        $result = $client->request('GET', ( !$override ? self::API_BASE_URL : '' ) . $url, $settings);

        $statusCode = $result->getStatusCode();
        if ($statusCode === 410) {
            Log::error(sprintf('%s has been removed - 410 Gone', $url));

            if ($override) {
                return ['status' => 410, 'message' => '[Error from Twitch API] 410 Gone - This API has been removed.'];
            }
        }

        /**
         * Hotfix to avoid polluting logs for removed API that doesn't use 410.
         */
        if ($statusCode === 404) {
            return ['status' => 404, 'message' => '[Error from Twitch API] 404 Not Found'];
        }

        return json_decode($result->getBody(), true);
    }

    /**
     * Get information from the base Kraken endpoint
     *
     * @param  string $token
     * @param  array $headers
     * @return TwitchApiController\get
     */
    public function base($token = '', $headers = [])
    {
        if (!empty($token)) {
            $headers['Authorization'] = 'OAuth ' . $token;
        }

        return $this->get('', false, $headers);
    }

    /**
     * Returns values from the Kraken channels endpoint.
     *
     * @param  string $channel Channel name
     * @param  array  $headers
     * @return TwitchApiController\get
     */
    public function channels($channel = '', $headers = [])
    {
        return $this->get('channels/' . $channel, false, $headers);
    }

    /**
     * Returns data from the 1st party, yet unofficial 'Products' API endpoint.
     * ! Depending on Twitch's mood, this may or may not disappear soon.
     *
     * @param string $channel
     *
     * @return TwitchApiController\get
     */
    public function channelProduct($channel = '')
    {
        $url = sprintf('https://api.twitch.tv/api/channels/%s/product', $channel);

        return $this->get($url, true, [
            'Client-ID' => 'kimne78kx3ncx6brgo4mv6wki5h1ko',
        ]);
    }

    /**
     * Returns the "/channel/:channel/follows" object.
     *
     * @param  string $channel
     * @param  int    $limit
     * @param  int    $offset
     * @param  string $direction
     * @param  array  $headers
     * @param  string $cursor
     * @return TwitchApiController\channels
     */
    public function channelFollows($channel = '', $limit = 25, $offset = 0, $direction = 'desc', $headers = [], $cursor = '')
    {
        $url = sprintf('%s/follows?limit=%d&offset=%d&direction=%s&cursor=%s', $channel, $limit, $offset, $direction, $cursor);
        return $this->channels($url, $headers);
    }

    /**
     * Gets channels/:channel/subscriptions data
     *
     * @param  string $channel     Channel name
     * @param  string $accessToken Authorization token
     * @param  int    $limit       Maximum numbers of objects
     * @param  int    $offset      Object offset for pagination.
     * @param  string $direction   Creation date sorting direction - Valid values are asc and desc.
     * @param  array  $headers
     * @return TwitchApiController\get
     */
    public function channelSubscriptions($channel = '', $accessToken = '', $limit = 25, $offset = 0, $direction = 'asc', $headers = [])
    {
        $params = [
            'limit=' . $limit,
            'offset=' . $offset,
            'direction=' . $direction
        ];

        $headers['Authorization'] = 'OAuth ' . $accessToken;
        return $this->get('channels/' . $channel . '/subscriptions?' . implode('&', $params), false, $headers);
    }

    /**
     * Gets channels/:channel/subscriptions/:user data
     *
     * @param  int $channel     Channel id
     * @param  int $user        User id
     * @param  string $accessToken Authorization token
     * @param  array  $headers
     * @return TwitchApiController\get
     */
    public function subscriptionRelationship($channel = '', $user = '', $accessToken = '', $headers = [])
    {
        if (empty($user)) {
            throw new Exception('You have to specify a user');
        }

        if (empty($channel)) {
            throw new Exception('You have to specify a channel');
        }

        if (!empty($accessToken)) {
            $headers['Authorization'] = 'OAuth ' . $accessToken;
        } else {
            throw new Exception('You to have provide an access token');
        }

        return $this->get('channels/' . $channel . '/subscriptions/'. $user, false, $headers);
    }

    /**
     * Gets chat/:channel/emoticons data
     *
     * @param  string $channel Channel name
     * @param  array  $headers
     * @return TwitchApiController\get
     */
    public function emoticons($channel = '', $headers = [])
    {
        return $this->get('chat/' . $channel . '/emoticons', false, $headers);
    }

    /**
     * Returns array of hosts for a specified channel
     *
     * @param  string $channel Channel name
     * @return array          List of channels hosting
     */
    public function hosts($channel = '')
    {
        if (empty($channel)) {
            throw new Exception('You have to specify a channel');
        }

        $url = sprintf('channels/%s/hosts', $channel);
        $hosts = $this->get($url, false, ['Accept' => 'application/vnd.twitchtv.v5+json']);
        return $hosts;
    }

    /**
     * Checks the API for the 'follow' relationship between a user and a channel.
     *
     * @param  string $user
     * @param  string $channel
     * @param  array  $headers
     * @return TwitchApiController\get
     */
    public function followRelationship($user = '', $channel = '', $headers = [])
    {
        if (empty($user)) {
            throw new Exception('You have to specify a user');
        }

        if (empty($channel)) {
            throw new Exception('You have to specify a channel');
        }

        return $this->get('users/' . $user . '/follows/channels/' . $channel, false, $headers);
    }

    /**
     * Returns values from the ingests endpoint from the API
     *
     * @return TwitchApiController\get
     */
    public function ingests($headers = ['Accept' => 'application/vnd.twitchtv.v5+json'])
    {
        return $this->get('ingests', false, $headers);
    }

    /**
     * Returns values from the Kraken streams endpoint.
     *
     * @param  string $channel Channel name
     * @param  array  $headers HTTP headers to pass through to TwitchApiController\get;
     * @return TwitchApiController\get
     */
    public function streams($channel = '', $headers = [])
    {
        return $this->get('streams/' . $channel, false, $headers);
    }

    /**
     * Returns values from the Kraken teams endpoint
     *
     * @param  string $team Team identifier
     * @param  array  $headers HTTP headers to pass through to TwitchApiController\get;
     * @return TwitchApiController\get
     */
    public function team($team = '', $headers = [])
    {
        return $this->get('teams/' . $team, false, $headers);
    }

    /**
     * Retrieves the user object specified by the username.
     *
     * @param  string $user The username
     * @return App\CachedTwitchUser
     */
    public function userByName($user = '')
    {
        $cachedUser = CachedUser::where(['username' => $user])->first();

        if (!empty($cachedUser)) {
            return $cachedUser;
        }

        $getUser = $this->get('users?login=' . $user, false, [
            'Accept' => 'application/vnd.twitchtv.v5+json'
        ]);

        if (empty($getUser['users'])) {
            throw new Exception('No user with the name "' . $user . '" found.');
        }

        $user = $getUser['users'][0];

        $checkId = CachedUser::where(['id' => $user['_id']])->first();
        if (!empty($checkId)) {
            $checkId->username = $user['name'];
            $checkId->save();
            return $checkId;
        }

        if (empty($cachedUser)) {
            $cachedUser = new CachedUser;
        }

        $cachedUser->id = $user['_id'];
        $cachedUser->username = $user['name'];
        $cachedUser->save();

        return $cachedUser;
    }

    /**
     * Returns values from the Kraken "users" endpoint.
     *
     * @param  string $user Username
     * @param  array  $headers HTTP headers to send with the request
     * @return Response
     */
    public function users($user = '', $headers = [])
    {
        return $this->get('users/' . $user, false, $headers);
    }

    /**
     * Returns a list of the channels a user is following.
     *
     * @param string $user
     * @param integer $limit
     * @param integer $offset
     * @param array $headers
     * @return void
     */
    public function userFollowsChannels($user = '', $limit = 25, $offset = 0, $direction = 'desc', $headers = [])
    {
        $url = sprintf('users/%s/follows/channels?limit=%d&offset=%d&direction=%s', $user, $limit, $offset, $direction);
        return $this->get($url, false, $headers);
    }

    /**
     * Returns result of the Kraken API for videos.
     *
     * @param  Request $request
     * @param  string  $channel         Channel name, can also be specified in the request.
     * @param  array   $broadcastTypes  Array of broadcast types
     * @param  integer $limit           Limit of highlights
     * @param  integer $offset          Offset
     * @param  array   $headers         Request headers
     * @return array                    JSON-decoded result of highlights endpoint
     */
    public function videos(Request $request, $channel, $broadcastType = ['all'], $limit = 1, $offset = 0, $headers = [])
    {
        $input = $request->all();
        $channel = $channel ?: $request->input('channel', null);
        if (empty($channel)) {
            throw new Exception('You have to specify a channel');
        }

        $limit = ($request->has('limit') ? intval($input['limit']) : $limit);
        $offset = ($request->has('offset') ? intval($input['offset']) : $offset);
        $format = '%s/videos?limit=%d&offset=%d&broadcast_type=%s';
        $url = sprintf($format, $channel, $limit, $offset, implode(',', $broadcastType));
        return $this->channels($url, $headers);
    }
}
