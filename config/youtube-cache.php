<?php
    return [
        /**
         * Cache YouTube search results (video ID lookup) for 3 hours (60 * 60 * 3)
         * Since this is primarily used for looking up music, it shouldn't cause many issues.
         */
        'search' => 10800,

        /**
         * Cache YouTube video details for 30 days (60 * 60 * 24 * 30)
         * Specifically extended video details for when we request more information of a video ID,
         * e.g. via latest_video
         */
        'video_details' => 2592000,

        /**
         * Cache YouTube livestream details for 3 hours (60 * 60 * 3)
         * Primarily used so that stream VODs will eventually be updated with the more permanent details, in which case the `video_details` cache will be used.
         */
        'livestream_details' => 10800,
    ];
