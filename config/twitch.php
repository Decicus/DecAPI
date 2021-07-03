<?php
    return [
        /**
         * $endpoint => $seconds (unless otherwise specified)
         *
         * $seconds = how long a value per channel / user should be cached.
         */
        'cache' => [
            'avatar' => 300,
            'game' => 60,
            // Stream title/status
            'status' => 60,
            'subpoints' => 120,
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
