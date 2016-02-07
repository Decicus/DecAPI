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
     * @param string $client_id     Twitch client ID
     * @param string $client_secret Twitch client Secret
     */
    public function __construct($client_id, $client_secret)
    {
        $this->twitchClientID = $client_id;
        $this->twitchClientSecret = $client_secret;
    }

    /**
     * Sends a standard GET request to the Twitch Kraken API, unless overridden.
     * @param  string $url    Endpoint URL, such as '/streams/channel-name';
     * @param  bool   $override Overrides the call to call the $url parameter directly instead of appending the $url parameter to the Kraken API
     * @param  array  $header Array of HTTP headers to send with the request. Client ID is already included in each request.
     * @return array          JSON-decoded object of the result
     */
    public function get($url, $override = false, $header = [])
    {
        $header['Client-ID'] = $this->twitchClientID;
        $header['http_errors'] = false;
        $client = new Client();
        $result = $client->request('GET', ( !$override ? self::API_BASE_URL : '' ) . $url, $header);
        return json_decode($result->getBody(), true);
    }

    public function streams($channel = '')
    {
        return $this->get('/streams/' . $channel);
    }

    public function team($team = '')
    {
        return $this->get('/teams/' . $team);
    }
}
