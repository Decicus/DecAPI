<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

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
     * Redirects Maja quotes to the old endpoint.
     *
     * @param  Request $request
     * @return Response
     */
    public function maja(Request $request)
    {
        $inputs = $request->all();

        $url = 'https://old.decapi.me/maja';

        $i = 0;
        foreach ($inputs as $name => $value) {
            $url .= ($i === 0 ? '?' : '&') . $name . '=' . $value;
            $i++;
        }

        return redirect($url);
    }
}
