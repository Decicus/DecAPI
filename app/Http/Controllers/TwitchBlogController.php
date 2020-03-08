<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Helpers\Helper;

class TwitchBlogController extends Controller
{
    /**
     * The base URL for the Twitch Blog.
     *
     * @var string
     */
    private $baseUrl = 'https://blog.twitch.tv';

    /**
     * Retrieves the latest Twitch blog post.
     *
     * ! Deprecated & removed since the Twitch blog is no longer hosted on Medium.
     *
     * @param  Request $request
     * @return Response
     */
    public function latest(Request $request)
    {
        return Helper::text('410 Gone - This feed (/twitch/blog/latest) has been removed.', 410);
    }
}
