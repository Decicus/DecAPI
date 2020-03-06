<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\Helpers\Helper;

class LeverController extends Controller
{
    /**
     * Retrieves the Twitch Lever info
     *
     * ! DEPRECATED: Returns `410 Gone`.
     *
     * @return Response
     */
    public function twitch()
    {
        return Helper::text('410 Gone - This feed (/lever/twitch) has been removed.', 410);
    }
}
