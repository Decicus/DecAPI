<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Helpers\Helper;
use Log;

class GeneralController extends Controller
{
    /**
     * Returns the home view for the homepage.
     *
     * @param  Request $request
     * @return Response
     */
    public function home(Request $request)
    {
        $data = ['page' => 'Home'];

        $messages = [
            'missing_scopes' => [
                'type' => 'warning',
                'text' => 'Authentication scopes have to be specified when authenticating.'
            ],
            'invalid_scope' => [
                'type' => 'warning',
                'text' => 'One of the authentication scopes specified are invalid.'
            ],
            'logged_out' => [
                'type' => 'success',
                'text' => 'You have been successfully logged out.'
            ]
        ];

        $message = $request->input('message', null);

        if (!empty($messages[$message])) {
            $data['message'] = $messages[$message];
        }

        return view('home', $data);
    }

    /**
     * As of 2019-01-19 this no longer redirects to V1 of DecAPI.
     * I doubt anyone still relies on it anyways. Shouldn't be necessary to keep around.
     *
     * The new fallback should just be a basic 404 plaintext page (to limit character spam due to bad bot implementation).
     *
     * @param  Request $request
     * @return Response
     */
    public function fallback(Request $request)
    {
        return Helper::text('404 Page Not Found', 404);
    }

    public function deprecated(Request $request)
    {
        $requestUrl = $request->url();
        $requestPath = parse_url($requestUrl, PHP_URL_PATH);

        $appName = config('app.name');
        return Helper::text(sprintf('[%s - %s] 410 Gone - This API has been deprecated/removed', $appName, $requestPath), 410);
    }

    /**
     * Displays the page for the Privacy Policy.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return void
     */
    public function privacyPolicy(Request $request)
    {
        return view('privacy-policy', ['page' => 'Privacy Policy']);
    }
}
