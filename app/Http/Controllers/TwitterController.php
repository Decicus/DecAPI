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
     * Returns the account age of the specified Twitter user.
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
     * Retrieves tweets from the specified user.
     *
     * @param  string  $name            Twitter username
     * @param  boolean $no_rts          'true' excludes retweets, 'false' includes them.
     * @param  boolean $exclude_replies Exclude replies or not, default: true.
     * @return array
     */
    private function getTweets($name = null, $no_rts = true, $exclude_replies = true)
    {
        $retweets = $no_rts ? 'false' : 'true';
        $exclude_replies = $exclude_replies ? 'true' : 'false';

        $tweet = Twitter::getUserTimeline([
            'screen_name' => $name,
            'count' => 200,
            'exclude_replies' => $exclude_replies,
            'include_rts' => $retweets,
            'tweet_mode' => 'extended',
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
        $search = $request->input('search', null);
        $onlyMedia = $request->input('only_media', null);
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
         * @var boolean
         */
        $excludeReplies = true;
        if ($request->exists('include_replies') || !empty($request->input('no_exclude_replies', null))) {
            $excludeReplies = false;
        }

        try {
            $tweets = $this->getTweets($name, $request->exists('no_rts'), $excludeReplies);

            if (empty($tweets)) {
                return Helper::text('No tweets were found for this user.');
            }

            if (count($tweets) < $skip) {
                return Helper::text('Skip count is higher than the amount of available tweets.');
            }

            if (!empty($search)) {
                $search = urldecode($search);
                $strict = $request->exists('strict');

                if ($strict === false) {
                    $search = strtolower($search);
                }

                $searchSkip = 0;
                foreach ($tweets as $current) {
                    $text = htmlspecialchars_decode($current->full_text);

                    if ($strict === false) {
                        $text = strtolower($text);
                    }

                    if (strpos($text, $search) !== false) {
                        if ($searchSkip === $skip) {
                            /**
                             * Makes sure that if the `$onlyMedia` parameter is included with
                             * the request, that the first tweet that matches the search query also
                             * includes some form of media (e.g. image or video).
                             */
                            if (!empty($onlyMedia)) {
                                if (empty($current->extended_entities)) {
                                    continue;
                                }

                                $ext = $current->extended_entities;
                                if (empty($ext->media)) {
                                    continue;
                                }
                            }

                            $tweet = $current;
                            break;
                        } else {
                            $searchSkip++;
                            continue;
                        }
                    }
                }

                if (!isset($tweet)) {
                    throw new Exception('No tweets found based on the search query.');
                }
            } else {
                $tweet = $tweets[$skip];
            }

            $text = [];
            if ($onlyUrl === false) {
                $tweetText = $tweet->full_text;

                /**
                 * Even if we access `$tweet->full_text`, it doesn't seem to work for retweets.
                 * Retweets are still truncated to 140 characters, so we need to extract the `full_text` from the retweet.
                 */
                if (isset($tweet->retweeted_status)) {
                    $retweet = $tweet->retweeted_status;
                    $tweetText = sprintf('RT @%s: %s', $retweet->user->screen_name, $retweet->full_text);
                }

                $text[] = str_replace(PHP_EOL, ' ', htmlspecialchars_decode($tweetText));
            }

            /**
             * Appends the amount of retweets the tweet has received.
             */
            if ($request->exists('retweets')) {
                $text[] = 'Retweets: ' . $tweet->retweet_count;
            }

            /**
             * Appends the amount of users that has favorited the tweet.
             */
            if ($request->exists('favorites')) {
                $text[] = 'Favorites: ' . $tweet->favorite_count;
            }

            /**
             * Appends the tweet URL to the tweet,
             * or displays only the URL, depending on
             * the query string parameters
             */
            if ($request->exists('url') || $onlyUrl) {
                $link = Twitter::linkTweet($tweet);

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

                $text[] = Helper::getDateDiff($tweet->created_at, time(), $precision) . " ago";
            }

            /**
             * Returns just the tweet ID.
             */
            if ($onlyId === true) {
                $text = [$tweet->id];
            }

            return Helper::text(implode(' - ', $text));
        } catch (Exception $e) {
            if ($e->getCode() === 401) {
                return Helper::text('Not authorized (Normally this means locked/private account)');
            }

            return Helper::text('[Error] - ' . trim($e->getMessage()));
        }
    }

    /**
     * Legacy support for /twitter/tweet. Similar to /twitter/tweet with "skip".
     *
     * @param  Request $request
     * @param  string  $tweet
     * @param  string  $name
     * @return Response
     */
    public function tweet(Request $request, $tweet = null, $name = null)
    {
        $count = $request->input('count', 1);
        $name = $name ?: $request->input('name', null);
        $withUrl = $request->exists('tweet_url');

        if ($count < 1) {
            return Helper::text('The "count" parameter has to be more than 0.');
        }

        /**
         * To exclude replies to other users or not.
         *
         * @var boolean
         */
        $excludeReplies = true;
        if ($request->exists('include_replies') || !empty($request->input('no_exclude_replies', null))) {
            $excludeReplies = false;
        }

        if (empty($name)) {
            return Helper::text('A Twitter username has to be specified');
        }

        try {
            $tweets = $this->getTweets($name, $request->exists('no_rts'), $excludeReplies);

            if (empty($tweets)) {
                return Helper::text('No tweets were found for this user.');
            }

            if (count($tweets) < $count) {
                return Helper::text('The "count" parameter is more than the amount of available tweets the user has.');
            }

            $tweet = $tweets[$count - 1];
            $text = [
                str_replace(PHP_EOL, ' ', htmlspecialchars_decode($tweet->full_text))
            ];

            if ($withUrl) {
                $text[] = Twitter::linkTweet($tweet);
            }

            return Helper::text(implode(' - ', $text));
        } catch (Exception $e) {
            if ($e->getCode() === 401) {
                return Helper::text('Not authorized (Normally this means locked/private account)');
            }

            return Helper::text('[Error] - ' . trim($e->getMessage()));
        }
    }
}
