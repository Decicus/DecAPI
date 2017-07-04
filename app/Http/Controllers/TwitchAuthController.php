<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Socialite;
use App\User;
use App\Helpers\Helper;
use Crypt;

class TwitchAuthController extends Controller
{
    use AuthenticatesAndRegistersUsers, ThrottlesLogins;

    /**
     * Array of available redirect targets
     *
     * @var array
     */
    private $redirects = [
        'home' => '/',
        'subcount' => '/twitch/subcount'
    ];

    /**
     * Array of available Twitch authentication scopes.
     *
     * @var array
     */
    private $scopes = [
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
        'viewing_activity_read'
    ];

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

        /**
         * This block is a bit of a dirty hotfix, to fix an issue with users using beta.decapi.me instead of decapi.me
         * Normally (according to various internet sources), changing the session cookie domain should've worked to fix this, but apparently not.
         *
         * Hopefully this doesn't have to linger around for months before I am able to look at this issue again.
         */
        $requestUrl = parse_url($request->url());
        $authUrl = parse_url(env('TWITCH_REDIRECT_URI', 'https://example.com/auth/twitch/callback'));
        if ($authUrl['host'] !== $requestUrl['host']) {
            return redirect($authUrl['scheme'] . '://' . $authUrl['host'] . '/auth/twitch?scopes=' . $scopes . '&redirect=' . $redirect);
        }

        if (empty($scopes)) {
            return Helper::message('missing_scopes');
        }

        if (!empty(trim($scopes))) {
            $scopes = explode(' ', trim($scopes));
            foreach ($scopes as $scope) {
                if (!in_array($scope, $this->scopes)) {
                    return Helper::message('invalid_scope');
                }
            }
        }

        if (!isset($this->redirects[$redirect])) {
            $redirect = 'home';
        }

        session()->put('redirect', $redirect);

        return Socialite::with('twitch')->scopes($scopes)->redirect();
    }

    /**
     * Handles return back from Twitch and takes care of authentication.
     *
     * @return Response
     */
    public function callback()
    {
        $redirect = session()->get('redirect', 'home');

        try {
            $user = Socialite::with('twitch')->user();
        } catch (Exception $e) {
            return redirect()->route('home');
        }

        $auth = User::firstOrCreate([
            'id' => $user->id
        ]);
        $auth->access_token = Crypt::encrypt($user->token);
        $auth->scopes = implode('+', $user->accessTokenResponseBody['scope']);
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
