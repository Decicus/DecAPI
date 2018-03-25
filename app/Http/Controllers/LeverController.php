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
     * @return Response
     */
    public function twitch()
    {
        $api = 'https://api.lever.co/v0/postings/twitch?mode=json';

        $client = new Client;
        $request = $client->request('GET', $api, [
            'http_errors' => false,
        ]);

        $feed = [
            0 => [
                'id' => 'error_has_occurred',
                'text' => 'An error has occurred',
                'description' => 'An error has occurred attempting to retrieve the latest jobs',
                'link' => 'https://jobs.lever.co/twitch/',
                'timestamp' => time(),
            ],
        ];

        if ($request->getStatusCode() === 200) {
            $data = json_decode($request->getBody(), true);
            $feed = [];

            foreach ($data as $post) {
                $feed[] = [
                    'guid' => $post['id'],
                    'text' => $post['text'],
                    'description' => $post['descriptionPlain'],
                    'link' => $post['hostedUrl'],
                    'timestamp' => intval($post['createdAt'] / 1000),
                ];
            }
        }

        return $this->feed('Lever - Twitch', 'Jobs listing at Twitch', 'https://jobs.lever.co/twitch/', 'https://lever-client-logos.s3.amazonaws.com/twitch.png', $feed);
    }
}
