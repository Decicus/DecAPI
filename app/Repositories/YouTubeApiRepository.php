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
        $this->shortsCutoff = new CarbonInterval('PT1M');
    }

    /**
     * Filters away any "shorts" videos (videos less than 1 minute long).
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
            $duration = new CarbonInterval($video->contentDetails->duration);
            if ($this->shortsCutoff->greaterThan($duration)) {
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
