<?php

namespace App\Repositories;

use Cache;
use Carbon\CarbonInterval;
use YouTube;

class YouTubeApiRepository
{
    /**
     * The cutoff point for shorts
     *
     * @var CarbonInterval
     */
    protected $shortsCutoff;

    public function __construct()
    {
        $this->shortsCutoff = new CarbonInterval('PT1M1S');
    }

    /**
     * Filters away (removes) any "shorts" videos (videos less than 1 minute long).
     * Input is expected to be an array of videos, as returned by `getVideoDetails()`.
     *
     * @param array $videos
     *
     * @return array
     */
    public function filterShorts($videos = [])
    {
        $filteredVideos = [];

        foreach ($videos as $id => $video) {
            $rawDuration = $video->contentDetails->duration;

            /**
             * Livestreams will not have a valid `duration` field until the VOD is ready.
             * Since `filterShorts()` is not supposed to affect livestreams, we implicitly include them.
             *
             * An alternative way of checking just livestreams would be: `$video->snippet->liveBroadcastContent === 'live'`
             */
            if ($rawDuration === 'P0D') {
                $filteredVideos[$id] = $video;
                continue;
            }

            $duration = new CarbonInterval($rawDuration);
            if ($this->shortsCutoff->greaterThanOrEqualTo($duration)) {
                continue;
            }

            $filteredVideos[$id] = $video;
        }

        return $filteredVideos;
    }

    /**
     * Filter away (removes) any currently *active* livestreams.
     * Does not affect stream VODs of completed livestreams.
     * Input is expected to be an array of videos, as returned by `getVideoDetails()`.
     *
     * @param array $videos
     *
     * @return array
     */
    public function filterLivestreams($videos = [])
    {
        $filteredVideos = [];

        foreach ($videos as $id => $video) {
            if ($video->snippet->liveBroadcastContent === 'live') {
                continue;
            }

            $filteredVideos[$id] = $video;
        }

        return $filteredVideos;
    }

    /**
     * Gets *extended* video details for the given video IDs.
     * This may serve video details from cache, but we're talking about things like the title, description, duration etc.
     * Data that should not update very often.
     *
     * @param array $videoIds A list of YouTube video IDs
     *
     * @return array
     */
    public function getVideoDetails($videoIds = [])
    {
        $cacheFormat = 'youtube_video_details_%s';
        $requestVideoIds = [];

        // First we figure out which video IDs we need to request
        foreach ($videoIds as $videoId) {
            $cacheKey = sprintf($cacheFormat, $videoId);

            if (Cache::has($cacheKey)) {
                continue;
            }

            $requestVideoIds[] = $videoId;
        }

        // Then we request the video details for the video IDs we need
        if (count($requestVideoIds) > 0) {
            $videoDetails = YouTube::getVideoInfo($requestVideoIds);

            foreach ($videoDetails as $videoDetail) {
                $videoId = $videoDetail->id;
                $cacheKey = sprintf($cacheFormat, $videoId);

                // Cache livestreams for a shorter time
                if ($videoDetail->snippet->liveBroadcastContent === 'live') {
                    Cache::put($cacheKey, $videoDetail, config('youtube-cache.livestream_details', 10800));
                    continue;
                }

                Cache::put($cacheKey, $videoDetail, config('youtube-cache.video_details', 2592000));
            }
        }

        // Finally we get the video details from the cache and put them in the `$videoDetails` array in order
        $videoDetails = [];
        foreach ($videoIds as $videoId) {
            $cacheKey = sprintf($cacheFormat, $videoId);

            if (Cache::has($cacheKey)) {
                $videoDetails[$videoId] = Cache::get($cacheKey);
            }
        }

        return $videoDetails;
    }
}
