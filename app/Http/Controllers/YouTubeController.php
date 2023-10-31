<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use YouTube;
use App\Helpers\Helper;
use Exception;
use Log;
use Cache;

use App\Repositories\YouTubeApiRepository;

class YouTubeController extends Controller
{
    /**
     * Default output format for latest video API.
     *
     * @var string
     */
    protected $defaultFormat = '{title} - {url}';

    /**
     * Valid 'variables' for the output format in latest video API.
     * Make sure to update the replacemtnts in both `latestVideo()` and `latestPlVideo()` if this array changes.
     *
     * @var array
     */
    protected $formatSearch = ['{id}', '{url}', '{title}'];

    /**
     * YouTube API repository, for handling logic around caching.
     *
     * @var YouTubeApiRepository
     */
    protected $api;

    public function __construct(YouTubeApiRepository $api)
    {
        $this->api = $api;
    }

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
        $format = $request->input('format', $this->defaultFormat);
        $exclude = $request->input('exclude', null);
        $include = $request->input('include', null);
        $noShorts = $request->input('no_shorts', '0');
        $excludeShorts = $noShorts !== '0' && $noShorts !== 'false';

        $noLivestream = $request->input('no_livestream', '0');
        $excludeLivestream = $noLivestream !== '0' && $noLivestream !== 'false';

        $fetchExtendedDetails = $excludeShorts || $excludeLivestream;

        if ($exclude !== null) {
            $exclude = trim($exclude);
        }

        if ($include !== null) {
            $include = trim($include);
        }

        if (empty(trim($format))) {
            $format = $this->defaultFormat;
        }

        $skip = intval($request->input('skip', 0));
        $max = 50;

        if ($skip >= $max) {
            $skip = 0;
        }

        /**
         * Channel IDs and old "user" URLs do not have spaces in them.
         *
         * Some users have specified their display names (example: "My Channel Name")
         * as their channel ID, which will just return an error from the YouTube API.
         *
         * To prevent unnecessary API requests, we're returning an error early as
         * this would be an invalid ID/user value anyways.
         */
        if (strpos($id, ' ') !== false) {
            return Helper::text(__('youtube.invalid_channel_value', [
                'type' => $type,
                'id' => $id,
            ]));
        }

        try {
            $parts = ['id', 'snippet', 'contentDetails'];
            switch ($type) {
                case 'user':
                    $channel = YouTube::getChannelByName($id, false, $parts);
                    break;

                default:
                    $channel = YouTube::getChannelById($id, false, $parts);
                    break;
            }

            if ($channel === false) {
                return Helper::text('The specified identifier is invalid.');
            }

            $uploadsPlaylist = $channel
                ->contentDetails
                ->relatedPlaylists
                ->uploads;

            $apiResults = YouTube::getPlaylistItemsByPlaylistId($uploadsPlaylist);
            $results = $apiResults['results'];

            /**
             * Sometimes YouTube's API returns bad data... I guess?
             */
            if (!is_array($results)) {
                return Helper::text('An error occurred retrieving videos for channel: ' . $request->input($type));
            }

            $videoIds = array_map(function($video) {
                return $video->contentDetails->videoId;
            }, $results);

            $videoDetails = [];
            if ($fetchExtendedDetails) {
                $videoDetails = $this->api->getVideoDetails($videoIds);
            }

            if ($excludeShorts) {
                $filteredVideos = $this->api->filterShorts($videoDetails);

                $results = array_filter($results, function($video) use ($filteredVideos) {
                    $videoId = $video->contentDetails->videoId;
                    return isset($filteredVideos[$videoId]);
                });
            }

            // Note: This flag only excludes a currently active livestream. Stream VODs are considered regular videos by the API.
            if ($excludeLivestream) {
                $filteredVideos = $this->api->filterLivestreams($videoDetails);

                $results = array_filter($results, function($video) use ($filteredVideos) {
                    $videoId = $video->contentDetails->videoId;
                    return isset($filteredVideos[$videoId]);
                });
            }

            /**
             * The YouTube API seems to return basic information about private videos as well,
             * even though we can't see any "real" information about them.
             *
             * This actually causes issues when we attempt to sort the videos,
             * as `videoPublishedAt` isn't a field that's available.
             *
             * Instead we filter the videos, so we only have the public ones left.
             */
            $results = array_filter($results,
                function($video) use ($exclude) {
                    $privacyStatus = $video->status->privacyStatus ?? 'private';
                    $isPublic = $privacyStatus === 'public';

                    if (empty($exclude)) {
                        return $isPublic;
                    }

                    $title = $video->snippet->title ?? '';
                    $isExcluded = stripos($title, $exclude) !== false;

                    return $isPublic && !$isExcluded;
                }
            );

            if (!empty($include)) {
                $results = array_filter($results,
                    function($video) use ($include) {
                        $title = $video->snippet->title ?? '';
                        return stripos($title, $include) !== false;
                    }
                );
            }

            if (empty($results)) {
                return Helper::text('This channel has no public videos matching the specified criteria.');
            }

            /**
             * Seems that YouTube sorts the API response for uploaded videos
             * by their upload timestamp, instead of their
             * "published publicly to YouTube" timestamp.
             *
             * With scheduled uploads, this can become an issue, so we're re-sorting
             * the whole array to take this into account.
             *
             * The fallback for `date('c', 0)` is used when `videoPublishedAt` isn't an available field.
             * All public videos (since we filter out any non-public videos earlier), _should_ have this field.
             * So it might be an unnecessary precaution.
             */
            usort($results, function($a, $b) {
                $publishOne = $a->contentDetails->videoPublishedAt ?? date('c', 0);
                $publishTwo = $b->contentDetails->videoPublishedAt ?? date('c', 0);

                return strtotime($publishTwo) - strtotime($publishOne);
            });

            $total = count($results);

            // Check if the request skips a valid amount of videos.
            if ($total < ($skip + 1)) {
                return Helper::text(sprintf('Channel only has %d public videos. Invalid skip count specified: %d.', $total, $skip));
            }

            $video = $results[$skip];
            // Title can sometimes includes HTML entities (such as '&amp;' instead of '&')
            $title = htmlspecialchars_decode($video->snippet->title, ENT_QUOTES);
            $videoId = $video->contentDetails->videoId;

            /**
             * See $this->formatSearch for a list of available variables.
             */
            $replacements = [
                // {id}
                $videoId,
                // {url}
                sprintf('https://youtu.be/%s', $videoId),
                // {title}
                $title,
            ];

            return Helper::text(str_replace($this->formatSearch, $replacements, $format));
        } catch (Exception $ex) {
            $exceptionMessage = sprintf('%s%s%s', $ex->getMessage(), PHP_EOL, $ex->getTraceAsString());
            $logResult = sprintf('An error occurred in /youtube/latest_video (channel: "%s", skip: %d): %s', $request->input($type), $skip, $exceptionMessage);
            Log::error($logResult);
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

        // Use default format, but make sure it's backwards compatible with the `separator` parameter.
        $defaultFormat = str_replace('-', $separator, $this->defaultFormat);
        $format = $request->input('format', $defaultFormat);

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
            // Title can sometimes includes HTML entities (such as '&amp;' instead of '&')
            $title = htmlspecialchars_decode($video->snippet->title, ENT_QUOTES);
            $videoId = $video->contentDetails->videoId;

            /**
             * See $this->formatSearch for a list of available variables.
             */
            $replacements = [
                // {id}
                $videoId,
                // {url}
                sprintf('https://youtu.be/%s', $videoId),
                // {title}
                $title,
            ];

            return Helper::text(str_replace($this->formatSearch, $replacements, $format));
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

        $normalizedSearch = strtolower(trim($search));
        $cacheKey = sprintf('youtube.video_id.%s', hash('sha256', $normalizedSearch));
        // Loaded from `config/youtube-cache.php` - Defaults to 3 hours
        $cacheTime = config('config.youtube-cache.search', 10800);

        /**
         * Check if search string has been cached
         */
        if (Cache::has($cacheKey)) {
            $videoId = Cache::get($cacheKey);

            if ($showUrl) {
                return Helper::text(sprintf('https://youtu.be/%s', $videoId));
            }

            return Helper::text($videoId);
        }

        // YouTube URL detected
        $parse = $this->parseURL($search);
        if ($parse !== false) {
            $video = YouTube::getVideoInfo($parse);
            if (!empty($video)) {
                $videoId = $video->id;
                Cache::put($cacheKey, $videoId, $cacheTime);

                if ($showUrl) {
                    return Helper::text(sprintf('https://youtu.be/%s', $videoId));
                }

                return Helper::text($videoId);
            }
        }

        // YouTube URL not detected, search for video
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

            return Helper::text('Invalid video URL, video ID or search string.');
        }

        Cache::put($cacheKey, $video, $cacheTime);

        if ($showUrl) {
            $video = sprintf('https://youtu.be/%s', $video);
        }

        return Helper::text($video);
    }

    /**
     * Parses a URL and attempts to retrieve the video ID.
     * This does not validate if the video ID is valid or not, though it is intended to be done in `videoId()`.
     *
     * @param  string $url        The URL to parse
     * @return string|false       The video ID or false if it's unable to find it.
     */
    private function parseURL($url)
    {
        $url = urldecode($url);

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parsed = null;
        try {
            $parsed = parse_url($url);
        } catch (Exception $e) {
            return false;
        }

        if (empty($parsed)) {
            return false;
        }

        $host = strtolower($parsed['host']);
        if ($host === 'youtu.be') {
            // Return video ID after `https://youtu.be/`
            return substr($parsed['path'], 1);
        }

        if ($host === 'youtube.com' || $host === 'www.youtube.com') {
            $query = [];
            $queryString = $parsed['query'] ?? '';
            parse_str($queryString, $query);

            if (empty($query['v'])) {
                return false;
            }

            if (!is_string($query['v'])) {
                return false;
            }

            return $query['v'];
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
