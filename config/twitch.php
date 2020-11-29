<?php
    return [
        /**
         * $endpoint => $seconds
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
        ],
    ];
?>
