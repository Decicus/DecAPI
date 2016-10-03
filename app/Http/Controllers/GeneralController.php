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
     * Redirects all routes that have not been added
     * to the new DecAPI, back to the old one.
     *
     * @param  Request $request
     * @return Response
     */
    public function fallback(Request $request)
    {
        $format = 'https://old.decapi.me/%s%s';
        $path = $request->path();
        $query = str_replace($request->url(), "", $request->fullUrl());
        return redirect(sprintf($format, $path, $query));
    }
}
