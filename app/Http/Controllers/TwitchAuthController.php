<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Socialite;
use Exception;
use App\User;
use App\Helpers\Helper;
use Crypt;
use Log;

use App\Repositories\TwitchApiRepository;
use GuzzleHttp\Client as HttpClient;
use App\Http\Resources\Twitch\AuthToken as TwitchAuthToken;

class TwitchAuthController extends Controller
{
    use ThrottlesLogins;

    /**
     * Array of available redirect targets
     *
     * @var array
     */
    private $redirects = [
        'home' => '/',
        'subage' => '/twitch/subage',
        'subcount' => '/twitch/subcount',
        'subpoints' => '/twitch/subpoints',
        'randomsub' => '/twitch/random_sub',
        'latestsub' => '/twitch/latest_sub'
    ];

    /**
     * Array of available Twitch authentication scopes.
     *
     * @var array
     */
    private $scopes = [
        // Kraken scopes
        'user_read',
        'user_blocks_edit',
        'user_blocks_read',
        'user_follows_edit',
        'channel_read',
        'channel_editor',
        'channel_commercial',
        'channel_stream',
        'channel_subscriptions',
        'user_subscriptions',
        'channel_check_subscription',
        'chat_login',
        'channel_feed_read',
        'channel_feed_edit',
        'collections_edit',
        'communities_edit',
        'communities_moderate',
        'viewing_activity_read',

        // Helix scopes
        'analytics:read:extensions',
        'analytics:read:games',
        'bits:read',
        'channel:read:subscriptions',
        'clips:edit',
        'user:edit',
        'user:edit:broadcast',
        'user:read:broadcast',
        'user:read:email',
    ];

    /**
     * Helix scopes that should be included when Kraken scopes are requested.
     *
     * @var array
     */
    private $krakenToHelixScopes = [
        'channel_subscriptions' => 'channel:read:subscriptions',
        'channel_check_subscription' => 'channel:read:subscriptions',
        'user_read' => 'user:read:email',
    ];

    /**
     * OAuth URLs for Twitch.
     *
     * @var string
     */
    private $authUrl = 'https://id.twitch.tv/oauth2/authorize';
    private $tokenUrl = 'https://id.twitch.tv/oauth2/token';

    /**
     * @var GuzzleHttp\Client
     */
    private $httpClient;

    /**
     * @var TwitchApiRepository
     */
    private $api;

    public function __construct(HttpClient $client, TwitchApiRepository $repository)
    {
        $this->httpClient = $client;
        $this->api = $repository;
    }

    /**
     * Redirect the user to the Twitch authentication page.
     *
     * @param  Request $request
     * @return Response
     */
    public function redirect(Request $request)
    {
        $scopes = $request->input('scopes', null);
        $redirect = $request->input('redirect', 'home');

        if (empty($scopes)) {
            return Helper::message('missing_scopes');
        }

        if (!empty(trim($scopes))) {
            $scopes = explode(' ', trim($scopes));
            foreach ($scopes as $scope) {
                if (!in_array($scope, $this->scopes)) {
                    return Helper::message('invalid_scope');
                }

                /**
                 * Make sure the Helix scope is included (for a smoother transition).
                 */
                if (!array_key_exists($scope, $this->krakenToHelixScopes)) {
                    continue;
                }

                $helixScope = $this->krakenToHelixScopes[$scope];
                if (!in_array($helixScope, $scopes)) {
                    $scopes[] = $helixScope;
                }
            }
        }

        if (!isset($this->redirects[$redirect])) {
            $redirect = 'home';
        }

        session()->flush();
        session()->put('redirect', $redirect);
        session()->put('scopes', implode('+', $scopes));

        $query = http_build_query([
            'client_id' => env('TWITCH_CLIENT_ID', null),
            'redirect_uri' => env('TWITCH_REDIRECT_URI', null),
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'force_verify' => 'true',
        ]);

        $url = sprintf('%s?%s', $this->authUrl, $query);
        return redirect()->away($url);
    }

    /**
     * Handles return back from Twitch and takes care of authentication.
     *
     * @param  Request $request
     * @return Response
     */
    public function callback(Request $request)
    {
        $redirect = session()->get('redirect', 'home');
        $scopes = session()->get('scopes');
        $code = $request->input('code', null);

        $authUrl = sprintf('%s?redirect=%s&scopes=%s', route('auth.twitch.base'), $redirect, $scopes);
        $viewData = [
            'authUrl' => $authUrl,
            'error' => null,
        ];

        if (empty($code)) {
            $viewData['error'] = $request->input('error_description', null);
            return view('auth.twitch', $viewData);
        }

        try {
            $response = $this->httpClient->request('POST', $this->tokenUrl, [
                'query' => [
                    'client_id' => env('TWITCH_CLIENT_ID', null),
                    'client_secret' => env('TWITCH_CLIENT_SECRET', null),
                    'redirect_uri' => env('TWITCH_REDIRECT_URI', null),
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $token = TwitchAuthToken::make($data)
                                    ->resolve();
        } catch (Exception $ex) {
            // TODO: Remove this once the problem is properly identified.
            Log::error('Exception thrown on Twitch authentication: ' . $ex->getMessage());
            Log::error('Request parameters: ' . json_encode($request->all()));
            Log::error('Session data: ' . json_encode($request->session()->all()));
            return view('auth.twitch', $viewData);
        }

        $this->api->setToken($token['access_token']);
        $users = $this->api->users();
        $user = $users[0];

        $auth = User::firstOrCreate([
            'id' => $user['id'],
        ]);
        $auth->access_token = Crypt::encrypt($token['access_token']);
        $auth->refresh_token = Crypt::encrypt($token['refresh_token']);
        $auth->scopes = implode('+', $token['scope']);
        $auth->expires = $token['expires'];
        $auth->save();

        Auth::login($auth, true);
        return redirect($this->redirects[$redirect]);
    }

    /**
     * Logs the user out.
     *
     * @return Response
     */
    public function logout()
    {
        Auth::logout();
        return Helper::message('logged_out');
    }
}
