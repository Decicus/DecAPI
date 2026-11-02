<?php

namespace App\Services;

use App\Exceptions\CurrencyApiException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;

class CurrencyApiClient
{
    /**
     * Base URLs for the currency data, tried in order.
     * The currency-api README asks clients to fall back to the Cloudflare mirror when jsDelivr fails.
     *
     * @var array
     */
    protected $baseUrls = [
        'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1',
        'https://latest.currency-api.pages.dev/v1',
    ];

    /**
     * Last-resort base URL for exchange rates only.
     * Same JSON shape, but only covers the ~30 ECB currencies (returns 404 for others),
     * so it is not used for the currency list. Rates by allratestoday.com.
     *
     * @var string
     */
    protected $ratesFallbackUrl = 'https://allratestoday.com/api/compat/currency-api/latest/v1';

    /**
     * An instance of GuzzleHttp\Client
     *
     * @var GuzzleHttp\Client
     */
    private $client;

    public function __construct(HttpClient $client)
    {
        $this->client = $client;
    }

    /**
     * Sends a GET request to the currency API.
     * Tries each base URL in order and returns the first decoded JSON response.
     *
     * @param string $url API endpoint (e.g. /currencies.min.json)
     * @param array $parameters Query (HTTP GET) parameters to pass along with the request.
     * @param array $headers Extra HTTP headers to send with the request.
     * @param array|null $baseUrls Base URLs to try, defaults to `$this->baseUrls`.
     *
     * @return array
     * @throws CurrencyApiException
     */
    public function get($url = '', $parameters = [], $headers = [], $baseUrls = null)
    {
        $clientParams = [
            'headers' => $headers,
            'query' => $parameters,
            // Forward HTTP responses on API errors.
            'http_errors' => false,
        ];

        // Pass custom user agent
        if (!isset($clientParams['headers']['User-Agent'])) {
            $clientParams['headers']['User-Agent'] = env('DECAPI_USER_AGENT', 'DecAPI/1.0.0 (https://github.com/Decicus/DecAPI)');
        }

        $error = 'No base URL configured';
        foreach ($baseUrls ?? $this->baseUrls as $baseUrl) {
            try {
                $response = $this->client->request('GET', sprintf('%s%s', $baseUrl, $url), $clientParams);
            } catch (GuzzleException $e) {
                $error = sprintf('Request to %s failed: %s', $baseUrl, $e->getMessage());
                continue;
            }

            if ($response->getStatusCode() !== 200) {
                $error = sprintf('Invalid response code received from the API: %s', $response->getStatusCode());
                continue;
            }

            $data = json_decode($response->getBody(), true);
            if (!is_array($data)) {
                $error = sprintf('Invalid JSON received from %s', $baseUrl);
                continue;
            }

            return $data;
        }

        throw new CurrencyApiException($error);
    }

    /**
     * Returns the list of currencies.
     *
     * @return array
     */
    public function getCurrencies()
    {
        $currencies = $this->get('/currencies.min.json');

        return [
            'currencies' => $currencies,
        ];
    }

    /**
     * Returns the exchange rates for the specified currency.
     *
     * @param string $currency
     *
     * @return array
     */
    public function getRates($currency)
    {
        $baseUrls = array_merge($this->baseUrls, [$this->ratesFallbackUrl]);

        return $this->get(sprintf('/currencies/%s.min.json', $currency), [], [], $baseUrls);
    }
}
