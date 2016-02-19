<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class TwitchAuthController extends Controller
{
    const AUTH_BASE_URL = 'https://api.twitch.tv/kraken/oauth2/authorize';

    public function auth(Request $request, $scopes = [])
    {
        
    }
}
