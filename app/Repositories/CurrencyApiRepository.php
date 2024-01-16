<?php

namespace App\Repositories;

use App\Services\CurrencyApiClient;

use Exception;
use App\Exceptions\CurrencyApiException;

class CurrencyApiRepository
{
    /**
     * @var App\Services\CurrencyApiClient
     */
    private $client;

    public function __construct(CurrencyApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Check if cached data should be refreshed.
     * Cached data is cached for an extended period in case of API issues.
     * If the API is down, we can still use the cached data for a while.
     *
     * @param array $data
     *
     * @return boolean
     */
    private function isCacheExpired($data)
    {
        if (!isset($data['expiry'])) {
            return true;
        }

        $expiry = now()->parse($data['expiry']);
        $hoursLeft = config('currency.cacheFetchHours', 24);
        if ($expiry->subHours($hoursLeft)->isPast()) {
            return true;
        }

        return false;
    }

    /**
     * Wrapper for fetching data from the cache, if it exists.
     *
     * @param string $cacheKey
     *
     * @return array|null
     */
    private function getFromCache($cacheKey)
    {
        if (!cache()->has($cacheKey)) {
            return null;
        }

        $data = cache()->get($cacheKey);
        if (!isset($data['expiry'])) {
            return null;
        }

        return $data;
    }

    /**
     * Wrapper for saving data to the cache.
     *
     * @param string    $cacheKey
     * @param array     $data
     * @param int       $expiry
     *
     * @return array
     */
    private function saveToCache($cacheKey, $data, $expiry)
    {
        $cacheExpiry = now()->addHours($expiry);
        $data['expiry'] = $cacheExpiry->toIso8601String();

        cache()->forget($cacheKey);
        cache()->put($cacheKey, $data, $cacheExpiry);
        return $data;
    }

    /**
     * Helper function for normalizing currency codes.
     *
     * @param string $currency
     *
     * @return string
     */
    private function normalize($currency)
    {
        return strtolower(trim($currency));
    }

    /**
     * Check if the specified currency exists.
     *
     * @param string $currency
     *
     * @return boolean
     */
    public function isValidCurrency($currency)
    {
        $currency = $this->normalize($currency);
        $currencies = $this->getCurrencies() ?? [];
        if (empty($currencies)) {
            return false;
        }

        $currencies = $currencies['currencies'] ?? [];
        return isset($currencies[$currency]);
    }

    /**
     * Returns the list of currencies.
     *
     * @return array
     */
    public function getCurrencies()
    {
        $cacheKey = config('currency.cacheKeyList', 'currency_list');
        $cacheData = $this->getFromCache($cacheKey);
        $hasCache = !empty($cacheData);

        if ($hasCache && !$this->isCacheExpired($cacheData)) {
            return $cacheData;
        }

        try {
            $response = $this->client->getCurrencies();
        } catch (Exception $e) {
            if ($hasCache) {
                return $cacheData;
            }

            throw new CurrencyApiException('Failed to fetch currencies.', $e->getCode(), $e);
        }

        $data = $this->saveToCache($cacheKey, $response, config('currency.cacheHours', 48));
        return $data;
    }

    /**
     * Get the exchange rates for the specified currency.
     *
     * @param string $currency
     *
     * @return array
     */
    public function getRates($currency)
    {
        $currency = $this->normalize($currency);
        $cacheKey = sprintf('currency_rates_%s', $currency);
        $cacheData = $this->getFromCache($cacheKey);
        $hasCache = !empty($cacheData);

        if ($hasCache && !$this->isCacheExpired($cacheData)) {
            return $cacheData;
        }

        try {
            $response = $this->client->getRates($currency);
        } catch (Exception $e) {
            if ($hasCache) {
                return $cacheData;
            }

            throw new CurrencyApiException('Failed to fetch currency rates.', $e->getCode(), $e);
        }

        $data = $this->saveToCache($cacheKey, $response, config('currency.cacheHours', 48));
        return $data;
    }

    /**
     * Converts a value from one currency to another.
     *
     * @param string $from  The currency to convert from.
     * @param string $to    The currency to convert to.
     * @param float $value  The value to convert.
     * @param int $round    How many decimals to round to. Defaults to 2.
     *
     * @return string  A formatted string with the conversion. For example: `1 USD = 0.88 EUR`
     */
    public function convert($from, $to, $value, $round = 2)
    {
        $from = $this->normalize($from);
        $to = $this->normalize($to);

        $rateData = $this->getRates($from);
        $rates = $rateData[$from] ?? [];
        $from = strtoupper($from);
        if (empty($rates)) {
            throw new CurrencyApiException(sprintf('Unable to find conversion rates for %s', $from));
        }

        $convert = $rates[$to] ?? null;
        $to = strtoupper($to);
        if (empty($convert)) {
            throw new CurrencyApiException(sprintf('Unable to find conversion rate - From %s to %s', $from, $to));
        }

        $calculate = round($value * $convert, $round);
        return sprintf('%s %s = %s %s', $value, $from, $calculate, $to);
    }
}
