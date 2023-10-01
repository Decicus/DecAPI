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

            // Cache avatars for up to 30 minutes
            'avatar' => 1800,
            'game' => 180,
            // Stream title/status
            'status' => 180,

            /**
             * Follower count of a channel
             */
            'followcount' => 120,

            /**
             * For `followed` / `followage` endpoints.
             * Cache time for a "follow relationship" between one channel and one user.
             *
             * 30 minutes
             */
            'follow_date' => 1800,
            // For any follow relationship that doesn't exist, we cache for shorter.
            'follow_date_empty' => 120,

            /**
             * Subscriptions meta API handler.
             * Currently being used for subpoints/subcount and it's *shared*.
             *
             * Meaning if subpoints is fetched + cached, then subcount will also be cached for the same amount of time.
             */
            'subscriptions_meta' => 60,

            /**
             * For fetching all subscriptions of a broadcaster.
             */
            'subscriptions_all' => 120,

            /**
             * TwitchApiRepository
             *
             * `channel_emotes` specifies minutes via `addMinutes()`.
             */
            'channel_emotes' => 60,

            /**
             * Used by `channelVideos()` in TwitchApiRepository.
             */
            'channel_videos' => 300,

            /**
             * Used by `streamByName()`/`streamById()` in TwitchApiRepository.
             *
             * This won't affect uptime and such, besides the fact that offline status
             * and online status may be delayed by a few minutes.
             */
            'stream_by_name' => 300,
            'stream_by_id' => 300,
        ],
    ];
?>
