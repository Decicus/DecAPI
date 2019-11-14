<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use Feed;
use App\Helpers\Helper;
use GuzzleHttp\Client;

class LeverController extends Controller
{
    /**
     * Returns an RSS feed of Lever jobs
     *
     * @param  string $title       Feed title
     * @param  string $description Feed description
     * @param  string $link        Feed link
     * @param  string $logo        Feed logo
     * @param  array  $post        Array of posts
     * @return Response
     */
    private function feed($title = 'Lever', $description = 'Lever description', $link = 'https://lever.co/', $logo = '', $posts = [])
    {
        $feed = new Feed;
        $feed->setView('vendor.feed.atom');
        $feed->title = $title;
        $feed->description = $description;
        $feed->link = $link;
        $feed->logo = $logo;
        $feed->setDateFormat('datetime');

        foreach ($posts as $post) {
            $date = date('c', $post['timestamp']);
            $feed->add($post['text'], $post['guid'], $post['link'], $date, $post['description'], $post['description']);
        }

        return $feed->render('atom');
    }

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
