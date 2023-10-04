<?php

/**
 * Currencies to fetch from this "API":
 * https://github.com/fawazahmed0/currency-api
 */
return [
    // Cache key for the currency *list*
    'cacheKeyList' => 'currencies',
    // Cache key prefix for the currency *rates*
    'cacheKeyPrefix' => 'currency_rates_',

    // Cache expiry in hours. We keep this at 48 hours by default.
    'cacheHours' => 48,

    // How many hours to wait before fetching the currency rates again.
    'cacheFetchHours' => 12,
];
