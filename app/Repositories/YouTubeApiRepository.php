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

    /**
     * The fallback duration for videos where we can't determine the duration
     *
     * @var string
     */
    private $fallbackDuration = 'PT2M0S';

    public function __construct()
    {
        $this->shortsCutoff = new CarbonInterval('PT1M1S');
    }

    /**
     * Check if a video status is a (future) premiere.
     *
     * @param string $status
     *
     * @return boolean
     */
    private function isPremiere($status = 'none')
    {
        return $status === 'upcoming';
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
            $liveContent = $video->snippet->liveBroadcastContent ?? 'none';
            if ($this->isPremiere($liveContent)) {
                $filteredVideos[$id] = $video;
                continue;
            }

            /**
             * Fallback duration of 2 minutes for videos without a duration
             * This may give some false positives, but we can fix those when they occur.
             */
            $rawDuration = $video->contentDetails->duration ?? $this->fallbackDuration;

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

            if ($this->isShort($video)) {
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
            $liveContent = $video->snippet->liveBroadcastContent ?? 'none';
            if ($liveContent === 'live' || $liveContent === 'upcoming') {
                continue;
            }

            $filteredVideos[$id] = $video;
        }

        return $filteredVideos;
    }


    /**
     * Filter videos by duration.
     *
     * @param array $details Array of video details
     * @param integer $minDuration Minimum duration in seconds
     * @param integer $maxDuration Maximum duration in seconds
     *
     * @return array Filtered array of video details
     */
    public function filterByDuration($details = [], $minDuration = 0, $maxDuration = 0)
    {
        if ($minDuration > 0) {
            $minimum = new CarbonInterval(seconds: $minDuration);

            $details = array_filter($details, function ($video) use ($minimum) {
                $rawDuration = $video->contentDetails->duration ?? $this->fallbackDuration;
                $duration = new CarbonInterval($rawDuration);

                return $duration->greaterThanOrEqualTo($minimum);
            });
        }

        if ($maxDuration > 0) {
            $maximum = new CarbonInterval(seconds: $maxDuration);

            $details = array_filter($details, function ($video) use ($maximum) {
                $rawDuration = $video->contentDetails->duration ?? $this->fallbackDuration;
                $duration = new CarbonInterval($rawDuration);

                return $duration->lessThanOrEqualTo($maximum);
            });
        }

        return $details;
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

    /**
     * Check if a video is considered a "Short".
     *
     * @param string|array $video Video ID or video details
     *
     * @return boolean
     */
    public function isShort($video = '')
    {
        if (empty($video)) {
            return false;
        }

        if (is_string($video)) {
            $videoDetails = $this->getVideoDetails([$video]);
            $video = reset($videoDetails);
        }

        $liveContent = $video->snippet->liveBroadcastContent ?? 'none';
        if ($this->isPremiere($liveContent)) {
            return false;
        }


        // TODO: Refactor into a single method instead of repeated logic (see: filterShorts())
        $rawDuration = $video->contentDetails->duration ?? $this->fallbackDuration;
        if ($rawDuration === 'P0D') {
            return false;
        }

        $duration = new CarbonInterval($rawDuration);
        return $this->shortsCutoff->greaterThanOrEqualTo($duration);
    }
}
