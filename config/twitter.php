<?php

return [
    'cache' => [
        'tweets' => 120,
        // Profile details can be cached for a longer period of time, since we currently only rely on "accountage" anyway.
        'user' => 3600,
    ],
];
