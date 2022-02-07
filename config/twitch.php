<?php
    return [
        /**
         * Cache settings
         *
         * $endpoint => $seconds (unless otherwise specified)
         *
         * $seconds = how long a value per channel / user should be cached.
         */
        'cache' => [
            /**
             * In theory we could cache this forever,
             * since we're caching the `created_at` timestamp.
             *
             * But since `accountage` may only either be used once or multiple times within a short timespan (a few hours),
             * we do a relatively short cache time to not unnecessarily cache something for extended periods.
             *
             * 21600 seconds = 6 hours
             *
             * This cache is shared between `accountage` & `creation`
             */
            'created' => 21600,

            'avatar' => 300,
            'game' => 60,
            // Stream title/status
            'status' => 60,

            /**
             * Follower count of a channel
             */
            'followcount' => 60,

            /**
             * Subscriptions meta API handler.
             * Currently being used for subpoints/subcount and it's *shared*.
             *
             * Meaning if subpoints is fetched + cached, then subcount will also be cached for the same amount of time.
             */
            'subscriptions_meta' => 60,

            'viewercount' => 60,

            /**
             * TwitchApiRepository
             *
             * `channel_emotes` specifies minutes via `addMinutes()`.
             */
            'channel_emotes' => 60,

            /**
             * Used by `channelVideos()` in TwitchApiRepository.
             */
            'channel_videos' => 180,
        ],
    ];
?>
