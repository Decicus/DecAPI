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
        'followage' => '/twitch/followage',
        'followed' => '/twitch/followed',
        'home' => '/',
        'subage' => '/twitch/subage',
        'subcount' => '/twitch/subcount',
        'subpoints' => '/twitch/subpoints',
        'randomsub' => '/twitch/random_sub',
        'latestsub' => '/twitch/latest_sub'
    ];

    /**
     * Minimum scope required for authentication.
     *
     * @var string
     */
    private $minimumScope = 'user:read:email';

    /**
     * Array of available Twitch authentication scopes.
     *
     * @var array
     */
    private $scopes = [
        'analytics:read:extensions',
        'analytics:read:games',
        'bits:read',
        'channel:edit:commercial',
        'channel:manage:broadcast',
        'channel:manage:extensions',
        'channel:manage:guest_star',
        'channel:manage:moderators',
        'channel:manage:polls',
        'channel:manage:predictions',
        'channel:manage:raids',
        'channel:manage:redemptions',
        'channel:manage:schedule',
        'channel:manage:videos',
        'channel:manage:vips',
        'channel:read:charity',
        'channel:read:editors',
        'channel:read:goals',
        'channel:read:guest_star',
        'channel:read:hype_train',
        'channel:read:polls',
        'channel:read:predictions',
        'channel:read:redemptions',
        'channel:read:stream_key',
        'channel:read:subscriptions',
        'channel:read:vips',
        'clips:edit',
        'moderation:read',
        'moderator:manage:announcements',
        'moderator:manage:automod',
        'moderator:manage:automod_settings',
        'moderator:manage:banned_users',
        'moderator:manage:blocked_terms',
        'moderator:manage:chat_messages',
        'moderator:manage:chat_settings',
        'moderator:manage:guest_star',
        'moderator:manage:shield_mode',
        'moderator:manage:shoutouts',
        'moderator:read:automod_settings',
        'moderator:read:blocked_terms',
        'moderator:read:chat_settings',
        'moderator:read:chatters',
        'moderator:read:followers',
        'moderator:read:guest_star',
        'moderator:read:shield_mode',
        'moderator:read:shoutouts',
        'user:edit',
        'user:edit:follows',
        'user:manage:blocked_users',
        'user:manage:chat_color',
        'user:manage:whispers',
        'user:read:blocked_users',
        'user:read:broadcast',
        'user:read:email',
        'user:read:follows',
        'user:read:subscriptions',
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
     * Generates a unique API token, utilizing `str_random` and checking if it already exists before returning it.
     * In other words, a paranoia check.
     *
     * @return string
     */
    private function generateUniqueApiToken()
    {
        $token = str_random(40);
        $exists = User::where('api_token', $token)->first();

        if (!empty($exists)) {
            return $this->generateUniqueApiToken();
        }

        return $token;
    }

    /**
     * Generate the authentication URL based on the requested scopes.
     *
     * @param array $scopes
     * @param bool $forceVerify Whether or not to force the user to re-authorize on Twitch's end.
     *
     * @return string
     */
    private function generateAuthUrl($scopes = [], $forceVerify = true)
    {
        // Always include the "minimum scope".
        if (!in_array($this->minimumScope, $scopes)) {
            $scopes[] = $this->minimumScope;
        }

        $query = http_build_query([
            'client_id' => env('TWITCH_CLIENT_ID', null),
            'redirect_uri' => env('TWITCH_REDIRECT_URI', null),
            'response_type' => 'code',
            'scope' => trim(implode(' ', $scopes)),
            'force_verify' => $forceVerify ? 'true' : 'false',
        ]);

        $url = sprintf('%s?%s', $this->authUrl, $query);
        return $url;
    }

    /**
     * Redirect the user to the Twitch authentication page.
     *
     * @param  Request $request
     * @return Response
     */
    public function redirect(Request $request)
    {
        $scopes = trim($request->input('scopes', ''));
        $redirect = $request->input('redirect', 'home');

        if (empty($scopes)) {
            return Helper::message('missing_scopes');
        }

        $scopes = explode(' ', $scopes);
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

        if (!isset($this->redirects[$redirect])) {
            $redirect = 'home';
        }

        session()->flush();
        session()->put('redirect', $redirect);
        session()->put('scopes', implode('+', $scopes));

        $url = $this->generateAuthUrl($scopes);
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

        /**
         * Since we don't know the authenticated user before we do the initial authentication
         * We'll do a check after the user has been authenticated and compare any existing scopes from the database.
         * If the user has requested a new scope, we'll redirect them back to Twitch to re-authorize to combine both sets of scopes.
         *
         * This will happen for any channel that has previously authenticated with for example subcount/subpoints,
         * but they're now authenticating for followage/followed access.
         *
         * Though, maybe it would simply be better to re-authorize with all scopes every time?
         */
        $existingScopes = trim($auth->scopes ?? '');
        if (!empty($existingScopes)) {
            $existingScopes = explode('+', $existingScopes);
            foreach ($existingScopes as $scope)
            {
                if (!in_array($scope, $token['scope'])) {
                    $merged = array_merge($existingScopes, $token['scope']);
                    $newScopes = array_unique($merged);

                    $authUrl = $this->generateAuthUrl($newScopes, false);
                    return redirect()->away($authUrl);
                }
            }
        }

        if (empty($auth->api_token)) {
            $auth->api_token = $this->generateUniqueApiToken();
        }

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
