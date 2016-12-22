<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Helpers\Helper;
use Vinelab\Rss\Rss;

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
     * @param  Request $request
     * @return Response
     */
    public function latest(Request $request)
    {
        $rss = new Rss;
        $feed = $rss->feed($this->baseUrl . '/feed');

        $skip = intval($request->input('skip', 0));

        $articles = $feed->articles();

        $post = $articles[$skip];
        $output = sprintf('%s - %s', $post->title, $post->link);

        return Helper::text($output);
    }
}
