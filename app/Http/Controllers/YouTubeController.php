<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use YouTube;
use App\Helpers\Helper;
use Exception;
use Log;

class YouTubeController extends Controller
{
    /**
     * Retrieves the latest public YouTube upload from the specified identifier.
     *
     * @param  Request $request
     * @return Response
     */
    public function latestVideo(Request $request)
    {
        $type = null;
        if ($request->has('user')) {
            $type = 'user';
        }

        if ($request->has('id')) {
            $type = 'id';
        }

        if (empty($type)) {
            return Helper::text('You need to specify a "user" (/user/ URLs) or an "id" (/channel/ URLs).');
        }

        $id = $request->input($type, null);
        $skip = intval($request->input('skip', 0));
        $max = 50;

        if ($skip >= $max) {
            $skip = 0;
        }

        try {
            switch ($type) {
                case 'user':
                    $channel = YouTube::getChannelByName($id);
                    break;

                default:
                    $channel = YouTube::getChannelById($id);
                    break;
            }

            if ($channel === false) {
                return Helper::text('The specified identifier is invalid.');
            }

            $uploadsPlaylist = $channel
                ->contentDetails
                ->relatedPlaylists
                ->uploads;

            $results = YouTube::getPlaylistItemsByPlaylistId($uploadsPlaylist);
            $results = $results['results'];

            if (empty($results)) {
                return Helper::text('This channel has no public videos.');
            }

            $total = count($results);

            // Check if the request skips a valid amount of videos.
            if ($total < ($skip + 1)) {
                return Helper::text(sprintf('Channel only has %d public videos. Invalid skip count specified: %d.', $total, $skip));
            }

            $video = $results[$skip];

            // Title can sometimes includes HTML entities (such as '&amp;' instead of '&')
            $title = htmlspecialchars_decode($video->snippet->title, ENT_QUOTES);
            return Helper::text($title . ' - https://youtu.be/' . $video->contentDetails->videoId);
        } catch (Exception $ex) {
            Log::error('An error occurred in /youtube/latest_video: ' . (string) $ex);
            return Helper::text('An error occurred retrieving videos for channel: ' . $request->input($type));
        }
    }

    /**
     * Retrieve the latest video in a YouTube playlist based on the playlist ID.
     *
     * @param  Request $request
     * @return Response
     */
    public function latestPlVideo(Request $request)
    {
        $id = $request->input('id', null);
        $skip = intval($request->input('skip', 0));
        $separator = $request->input('separator', '-');

        if (empty($id) || trim($id) === '') {
            return Helper::text('A playlist ID has to be specified.');
        }
        try {
            $results = YouTube::getPlaylistItemsByPlaylistId($id)['results'];

            $count = count($results);
            if ($skip > $count - 1) {
                $error = sprintf('Skip count (%d) has to be lower than the amount of available videos in playlist (%d).', $skip, $count);
                return Helper::text($error);
            }

            $video = $results[$skip];
            $format = sprintf('%s %s https://youtu.be/%s', $video->snippet->title, $separator, $video->contentDetails->videoId);
            return Helper::text($format);
        } catch (Exception $e) {
            return Helper::text('An error occurred retrieving playlist items with the playlist ID: ' . $id);
        }
    }

    /**
     * Searches the YouTube API for the specified string, if it's a video ID, it'll just return the video ID.
     * If it's a valid search string, and it finds a result, it'll return the video ID of the first result.
     * If neither, it will either return nothing (if the word "nightbot" is found in the user agent).
     * Or it will return an error message.
     *
     * @param  Request $request
     * @param  string  $videoId
     * @param  string  $search  Search string or video ID/URL
     * @return Response
     */
    public function videoId(Request $request, $videoId = null, $search = null)
    {
        $search = $search ?: $request->input('search', null);
        $showUrl = $request->exists('show_url');

        if (empty($search)) {
            // Send an empty response so that Nightbot doesn't attempt to 'search' the YouTube API with the returned string.
            if ($this->isNightbot($request)) {
                return Helper::text('');
            }

            return Helper::text('No search parameter specified.');
        }

        // YouTube URL detected
        $parse = $this->parseURL($search);
        if ($parse !== false) {
            $video = YouTube::getVideoInfo($parse);

            if (!empty($video)) {
                $video = $video->id;
            }
        }

        if ($parse === false) {
            $parameters = [
                'q' => $search,
                'type' => 'video',
                'part' => 'id',
                'maxResults' => 5,
            ];

            try {
                $videos = YouTube::searchAdvanced($parameters);

                /**
                 * For some reason, certain keywords (only one that I saw was "headlines") would error out.
                 *
                 * This is because the YouTube Data API decides to return a specific channel ID for their
                 * auto-generated "News" channel, even though type is set to 'video'.
                 *
                 * Specifically talking about this channel: https://www.youtube.com/channel/UCYfdidRxbB8Qhf0Nx7ioOYw
                 *
                 * This is an ugly workaround that I did to prevent it from giving an error whenever
                 * someone searches for songs like "Drake - Headlines".
                 */
                if (!empty($videos)) {
                    foreach ($videos as $searchResult) {
                        if (property_exists($searchResult->id, 'videoId')) {
                            $video = $searchResult->id->videoId;
                            break;
                        }
                    }
                }
            } catch (Exception $ex) {
                Log::error(sprintf('An error occurred in /youtube/videoid (search query: "%s" ): %s', $search, (string) $ex));
                if ($this->isNightbot($request)) {
                    return Helper::text('');
                }

                return Helper::text('An error occurred when searching: ' . $search);
            }
        }

        if (empty($video)) {
            if ($this->isNightbot($request)) {
                return Helper::text('');
            }

            return Helper::text('Invalid video ID or search string.');
        }

        if ($showUrl) {
            $video = 'https://youtu.be/' . $video;
        }

        return Helper::text($video);
    }

    /**
     * Parses a URL and attempts to retrieve the video ID.
     *
     * @param  string $url The URL to parse
     * @return mixed       The video ID or false if it's unable to find it.
     */
    private function parseURL($url)
    {
        $url = urldecode($url);

        if (stristr($url, 'youtu.be/')) {
            preg_match('/(https:|http:|)(\/\/www\.|\/\/|)(.*?)\/(.{11})/i', $url, $id);
            return $id[4];
        } else {
            preg_match('/(https:|http:|):(\/\/www\.|\/\/|)(.*?)\/(embed\/|watch.*?v=|)([a-z_A-Z0-9\-]{11})/i', $url, $id);
            return (!empty($id) ? $id[5] : false);
        }

        return false;
    }

    /**
     * Checks if the request is done using Nightbot's "URL fetcher"
     *
     * @param  Request  $request
     * @return boolean
     */
    private function isNightbot(Request $request)
    {
        if (strpos($request->server('HTTP_USER_AGENT'), 'Nightbot') !== false) {
            return true;
        }

        return false;
    }
}
