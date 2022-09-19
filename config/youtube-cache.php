<?php
    return [
        /**
         * Cache YouTube search results (video ID lookup) for 3 hours (60 * 60 * 3)
         * Since this is primarily used for looking up music, it shouldn't cause many issues.
         */
        'search' => 10800,
    ];
