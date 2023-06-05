<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use Twitter;
use App\Helpers\Helper;
use Exception;

use App\Repositories\TwitterApiRepository;
use App\Exceptions\TwitterApiException;

class TwitterController extends Controller
{
    /**
     * @var App\Repositories\TwitterApiRepository
     */
    private $api;

    public function __construct(TwitterApiRepository $api) {
        $this->api = $api;
    }

    /**
     * Returns the account age of the specified Twitter user.
     *
     * TODO: Fix/remove
     *
     * @param  Request $request
     * @param  string  $name
     * @return Response
     */
    public function accountAge(Request $request, $name = null)
    {
        if (empty($name)) {
            return Helper::text('You have to specify a (user)name.');
        }

        $precision = intval($request->input('precision', 3));
        try {
            $user = Twitter::getUsers([
                'screen_name' => $name,
            ]);

            $timeDiff = Helper::getDateDiff($user->created_at, time(), $precision);
            return Helper::text($timeDiff);
        } catch (Exception $ex) {
            return Helper::text($ex->getMessage());
        }
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

        /**
         * TODO: Re-implement search & skip
         */
        $search = $request->input('search', null);
        $skip = intval($request->input('skip', 0));

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

        /**
         * To exclude replies to other users or not.
         *
         * TODO: Re-implement this
         *
         * @var boolean
         */
        $excludeReplies = true;
        if ($request->exists('include_replies') || !empty($request->input('no_exclude_replies', null))) {
            $excludeReplies = false;
        }

        $tweets = [];
        try {
            $tweets = $this->api->getTweets($name);
        }
        catch (TwitterApiException $ex) {
            return Helper::text(sprintf('An error occurred while fetching tweets for %s: %s', $name, $ex->getMessage()));
        }

        if (empty($tweets)) {
            return Helper::text('No tweets were found for this user.');
        }

        // Remove pinned
        $tweets = array_filter($tweets, function($tweet) {
            return $tweet['pinned'] === false;
        });

        $noRetweets = $request->exists('no_rts');
        if ($noRetweets) {
            $tweets = array_filter($tweets, function($tweet) {
                return $tweet['retweet'] === false;
            });
        }

        if (empty($tweets)) {
            return Helper::text('No tweets (matching the filters) were found for this user');
        }

        $tweet = current($tweets);

        $text = [];
        if ($onlyUrl === false) {
            $tweetText = $tweet['textInline'];

            /**
             * Even if we access `$tweet->full_text`, it doesn't seem to work for retweets.
             * Retweets are still truncated to 140 characters, so we need to extract the `full_text` from the retweet.
             */
            if ($tweet['retweet'] === true) {
                $tweetText = sprintf('RT @%s: %s', $tweet['author']['username'], $tweetText);
            }

            $text[] = str_replace(PHP_EOL, ' ', $tweetText);
        }

        /**
         * Appends the tweet URL to the tweet,
         * or displays only the URL, depending on
         * the query string parameters
         */
        if ($request->exists('url') || $onlyUrl) {
            $link = $tweet['url'];

            /**
             * Shortens the URL using TinyURL
             */
            if ($request->exists('shorten')) {
                $link = Helper::get('http://tinyurl.com/api-create.php?url=' . $link, [
                    'User-Agent' => env('SITE_TITLE') . '/Twitter'
                ], false);
            }

            $text[] = $link;
        }

        /**
         * Appends length of time since tweet was posted.
         */
        if ($request->exists('howlong')) {
            $precision = 4;
            if ($request->has('precision')) {
                $precision = intval($request->input('precision'));
            }

            $text[] = Helper::getDateDiff($tweet['created_at'], time(), $precision) . " ago";
        }

        if ($onlyId) {
            return Helper::text($tweet['id']);
        }

        return Helper::text(implode(' - ', $text));
    }
}
