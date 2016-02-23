<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;

class TwitchAuthController extends Controller
{
    const API_BASE_URL = 'https://api.twitch.tv/kraken/';
    const AUTH_BASE_URL = 'https://api.twitch.tv/kraken/oauth2/authorize';
    private $states = [
        'subcount' => [
            'redirect' => '/twitch/subcount',
            'scopes' => ['user_read', 'channel_subscriptions']
        ]
    ];
    private $redirectUrl;
    private $twitchApi;

    /**
     * Initializes the controller
     */
    public function __construct()
    {
        $this->redirectUrl = url('/auth/twitch');
        $this->twitchApi = new TwitchApiController(env('TWITCH_CLIENT_ID'), env('TWITCH_CLIENT_SECRET'));
    }

    /**
     * Handle Twitch authentication
     * @param  Request $request
     * @return Redirect
     */
    public function auth(Request $request)
    {
        // TODO: Handle invalid inputs somehow (move redirect to 404s).
        $inputs = $request->all();

        if(empty($inputs['code']) || empty($inputs['state'])) {
            if(empty($inputs['page'])) {
                return redirect(url('/?404'));
            }
            $page = $inputs['page'];

            if(empty($this->states[$page])) {
                return redirect(url('/?404'));
            }

            $authUrl = $this->generateAuthUrl($this->states[$page]['scopes'], $page);
            return redirect($authUrl);
        }

        $code = $inputs['code'];
        $state = $inputs['state'];
        if(empty($this->states[$state])) {
            return redirect(url('/?404'));
        }
        $redirect = $this->states[$state]['redirect'];
        $accessToken = $this->getAccessToken($code, $state, $this->redirectUrl);
        if(empty($accessToken['access_token'])) {
            return redirect(url('/?404'));
        }
        $token = $accessToken['access_token'];
        $checkToken = $this->twitchApi->get('?oauth_token=' . $token);
        if(!$checkToken['token']['valid']) {
            $authUrl = $this->generateAuthUrl($this->states[$page]['scopes'], $state);
            return redirect($authUrl);
        }
        $request->session()->put('subcount_at', $token);
        $request->session()->put('username', $checkToken['token']['user_name']);
        return redirect($redirect);
    }

    /**
     * Generates Twitch authentication URL
     * @param  array $scopes Array of authentication scopes
     * @param  string $state  State to redirect back to
     * @return string
     */
    protected function generateAuthUrl($scopes = [], $state = '')
    {
        $clientId = env('TWITCH_CLIENT_ID', null);
        $clientSecret = env('TWITCH_CLIENT_SECRET', null);

        if(empty($clientId) || empty($clientSecret)) {
            // TODO: Handle unspecified client ID and secret.
            return;
        }
        $params = [
            'response_type=code',
            'client_id=' . $clientId,
            'redirect_uri=' . $this->redirectUrl,
            'scope=' . implode('+', $scopes),
            'state=' . $state,
            'force_verify=true'
        ];

        return self::AUTH_BASE_URL . '?' . implode('&', $params);
    }

    /**
     * Retrieves the access token using the authorization code and state parameter passed after authenticating with Twitch.
     * @param  string $code  Authorization code
     * @param  string $state State
     * @return array
     */
    public function getAccessToken($code, $state)
    {
        $clientId = env('TWITCH_CLIENT_ID', null);
        $clientSecret = env('TWITCH_CLIENT_SECRET', null);

        if(empty($clientId) || empty($clientSecret)) {
            // TODO: Handle unspecified client ID and secret.
            return;
        }
        $client = new Client();
        $request = $client->request('POST', self::API_BASE_URL . 'oauth2/token', [
            'form_params' => [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->redirectUrl,
                'code' => $code,
                'state' => $state
            ],
            'http_errors' => false
        ]);
        $response = json_decode($request->getBody(), true);
        return $response;
    }
}
