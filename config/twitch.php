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
            'avatar' => 300,
            'game' => 60,
            // Stream title/status
            'status' => 60,

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
        ],
    ];
?>
