<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use GuzzleHttp\Client;

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
    public function __construct($clientId, $clientSecret)
    {
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
        $settings['headers']['Client-ID'] = $this->twitchClientID;
        $settings['http_errors'] = false;
        $client = new Client();
        $result = $client->request('GET', ( !$override ? self::API_BASE_URL : '' ) . $url, $settings);
        return json_decode($result->getBody(), true);
    }

    /**
     * Returns values from the Kraken channels endpoint.
     *
     * @param  string $channel Channel name
     * @return TwitchApiController\get
     */
    public function channels($channel = '')
    {
        return $this->get('channels/' . $channel);
    }

    /**
     * Gets channels/:channel/subscriptions data
     *
     * @param  string $channel     Channel name
     * @param  string $accessToken Authorization token
     * @param  int    $limit       Maximum numbers of objects
     * @param  int    $offset      Object offset for pagination.
     * @param  string $direction   Creation date sorting direction - Valid values are asc and desc.
     * @return TwitchApiController\get
     */
    public function channelSubscriptions($channel = '', $accessToken = '', $limit = 25, $offset = 0, $direction = 'asc')
    {
        $params = [
            'oauth_token=' . $accessToken,
            'limit=' . $limit,
            'offset=' . $offset,
            'direction=' . $direction
        ];
        return $this->get('channels/' . $channel . '/subscriptions?' . implode('&', $params));
    }

    /**
     * Gets chat/:channel/emoticons data
     *
     * @param  string $channel Channel name
     * @return TwitchApiController\get
     */
    public function emoticons($channel = '')
    {
        return $this->get('chat/' . $channel . '/emoticons');
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

        $channelInfo = $this->channels($channel);
        if (!empty($channelInfo['error'])) {
            return $channelInfo;
        }

        $userId = $channelInfo['_id'];
        $hostUrl = 'https://tmi.twitch.tv/hosts?include_logins=1&target={_id}';
        $hosts = $this->get(str_replace('{_id}', $userId, $hostUrl), true);
        return $hosts['hosts'];
    }

    /**
     * Checks the API for the 'follow' relationship between a user and a channel.
     *
     * @param  string $user
     * @param  string $channel
     * @return TwitchApiController\get
     */
    public function followRelationship($user = '', $channel = '')
    {
        if (empty($user)) {
            throw new Exception('You have to specify a user');
        }

        if (empty($channel)) {
            throw new Exception('You have to specify a channel');
        }

        return $this->get('users/' . $user . '/follows/channels/' . $channel);
    }

    /**
     * Returns values from the ingests endpoint from the API
     *
     * @return TwitchApiController\get
     */
    public function ingests()
    {
        return $this->get('ingests');
    }

    /**
     * Returns values from the Kraken streams endpoint.
     *
     * @param  string $channel Channel name
     * @return TwitchApiController\get
     */
    public function streams($channel = '')
    {
        return $this->get('streams/' . $channel);
    }

    /**
     * Returns values from the Kraken teams endpoint
     *
     * @param  string $team Team identifier
     * @return TwitchApiController\get
     */
    public function team($team = '')
    {
        return $this->get('teams/' . $team);
    }

    /**
     * Returns values from the Kraken "users" endpoint.
     *
     * @param  string $user Username
     * @return Response
     */
    public function users($user = '')
    {
        return $this->get('users/' . $user);
    }

    /**
     * Returns result of the Kraken API for videos.
     *
     * @param  Request $request
     * @param  string  $channel         Channel name, can also be specified in the request.
     * @param  array   $broadcastTypes  Array of broadcast types
     * @param  integer $limit           Limit of highlights
     * @param  integer $offset          Offset
     * @param  bool    $broadcasts      Returns only past broadcasts on true, highlights on false
     * @param  bool    $hls             Returns only HLS VODs when true, non-HLS VODs on false
     * @return array                    JSON-decoded result of highlights endpoint
     */
    public function videos(Request $request, $channel, $broadcastType = ['all'], $limit = 1, $offset = 0, $broadcasts = false, $hls = false)
    {
        $input = $request->all();
        $channel = $channel ?: $request->input('channel', null);
        if (empty($channel)) {
            throw new Exception('You have to specify a channel');
        }

        $limit = ($request->has('limit') ? intval($input['limit']) : $limit);
        $offset = ($request->has('offset') ? intval($input['offset']) : $offset);
        $format = '%s/videos?limit=%d&offset=%d&broadcasts=%s&hls=%s&broadcast_type=%s';
        $url = sprintf($format, $channel, $limit, $offset, $broadcasts, $hls, implode(',', $broadcastType));
        return $this->channels($url);
    }
}
