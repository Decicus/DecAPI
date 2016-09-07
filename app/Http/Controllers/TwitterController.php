<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use Twitter;
use App\Helpers\Helper;
use Exception;
class TwitterController extends Controller
{
    /**
     * Retrieves tweets from the specified user.
     *
     * @param  string  $name   Twitter username
     * @param  boolean $no_rts 'true' excludes retweets, 'false' includes them.
     * @return array
     */
    private function getTweets($name = null, $no_rts = true, $exclude_replies = true)
    {
        $retweets = $no_rts ? 'false' : 'true';
        $exclude_replies = $exclude_replies ? 'true' : 'false';

        $tweet = Twitter::getUserTimeline([
            'screen_name' => $name,
            'count' => 200,
            'exclude_replies' => 'true',
            'include_rts' => $retweets
        ]);

        return $tweet;
    }

    /**
     * Fetches the latest tweet for the specified user
     *
     * @param  Request $request
     * @param  string  $latest  Route: Can be latest(.php) or latest_url(.php)
     * @param  string  $name    Twitter username
     * @return Response
     */
    public function latest(Request $request, $latest = null, $name = null)
    {
        $name = $name ?: $request->input('name', null);

        if (empty($name)) {
            return Helper::text('You have to specify a (user)name.');
        }

        /**
         * Route: /twitter/latest_url
         *
         * @var boolean
         */
        $onlyUrl = (strpos($latest, 'latest_url') === false ? false : true);

        /**
         * Route: /twitter/latest_id
         *
         * @var boolean
         */
        $onlyId = (strpos($latest, 'latest_id') === false ? false : true);

        try {
            $tweets = $this->getTweets($name, $request->exists('no_rts'));

            if (empty($tweets)) {
                return Helper::text('No tweets were found for this user.');
            }

            $first = $tweets[0];
            $text = [];
            if ($onlyUrl === false) {
                $text[] = str_replace(PHP_EOL, ' ', htmlspecialchars_decode($first->text));
            }

            if ($request->exists('url') || $onlyUrl) {
                $link = Twitter::linkTweet($first);

                if ($request->exists('shorten')) {
                    $link = Helper::get('http://tinyurl.com/api-create.php?url=' . $link, [
                        'User-Agent' => env('SITE_TITLE') . '/Twitter'
                    ], false);
                }

                $text[] = $link;
            }

            if ($request->exists('howlong')) {
                $precision = 4;
                if ($request->has('precision')) {
                    $precision = intval($request->input('precision'));
                }

                $text[] = Helper::getDateDiff($first->created_at, time(), $precision) . " ago";
            }

            if ($onlyId === true) {
                $text = [$first->id];
            }

            return Helper::text(implode(' - ', $text));
        } catch (Exception $e) {
            if ($e->getCode() === 401) {
                return Helper::text('Not authorized (Normally this means locked/private account)');
            }

            return Helper::text('An error has occurred: ' . trim($e->getMessage()));
        }
    }
}
