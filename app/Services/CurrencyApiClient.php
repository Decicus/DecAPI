<?php

namespace App\Services;

use App\Exceptions\CurrencyApiException;
use GuzzleHttp\Client as HttpClient;

class CurrencyApiClient
{
    /**
     * Base URL for the currency data.
     *
     * @var string
     */
    protected $baseUrl = 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1';

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
     * Returns the decoded JSON response.
     *
     * @param string $url API endpoint (e.g. /currencies.min.json)
     * @param array $parameters Query (HTTP GET) parameters to pass along with the request.
     * @param array $headers Extra HTTP headers to send with the request.
     *
     * @return array
     * @throws CurrencyApiException
     */
    public function get($url = '', $parameters = [], $headers = [])
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

        $response = $this->client->request('GET', sprintf('%s%s', $this->baseUrl, $url), $clientParams);

        if ($response->getStatusCode() !== 200) {
            throw new CurrencyApiException(sprintf('Invalid response code received from the API: %s', $response->getStatusCode()));
        }

        return json_decode($response->getBody(), true);
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
        return $this->get(sprintf('/currencies/%s.min.json', $currency));
    }
}
