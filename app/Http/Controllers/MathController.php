<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Helpers\Helper;
use Exception;
use GuzzleHttp\Client as HttpClient;

class MathController extends Controller
{
    /**
     * Base URL for math.js API.
     *
     * @var string
     */
    private $mathBaseUrl = 'https://math.decapi.net/?expr=';

    /**
     * HTTP client
     *
     * @var GuzzleHttp\Client
     */
    private $client;

    /**
     * HTTP client settings
     *
     * @var array
     */
    private $clientSettings = [
        'http_errors' => false,
    ];

    public function __construct(HttpClient $client)
    {
        $this->client = $client;
    }

    /**
     * Evaluates a math expression.
     *
     * @param  Request $request
     * @return Response
     */
    public function evaluate(Request $request)
    {
        $exp = $request->input('exp', null);
        $round = intval($request->input('round', 0));

        if (empty($exp)) {
            return Helper::text('A math expression (exp) has to be specified.');
        }

        $decimalSeparator = $request->input('separator', '.');

        // Spaces are unnecessary in the expression, and will actually error the evaluation
        // if spaces are used as a 'thousand separator'
        $exp = str_replace(' ', '', $exp);

        if ($decimalSeparator === '.') {
            // API does not like comma used as a thousand separator
            $exp = str_replace(',', '', $exp);
        } else {
            $exp = str_replace('.', '', $exp);
            $exp = str_replace($decimalSeparator, '.', $exp);
        }

        try {
            $url = sprintf('%s%s', $this->mathBaseUrl, urlencode($exp));
            $response = $this->client->request('GET', $url, $this->clientSettings);

            if ($response->getStatusCode() !== 200) {
                return Helper::text(sprintf('An error occurred calculating the expression: %s', $exp));
            }

            $result = (string) $response->getBody();
            if (strlen($result) === 0) {
                return Helper::text('No result.');
            }

            if ($request->exists('round') === true) {
                // Before we can use `round()` we need to convert it to a float.
                $result = (float) $result;
                $result = round($result, $round);
            }

            return Helper::text($result);
        } catch (Exception $e) {
            return Helper::text('An error occurred evaluating: ' . $exp);
        }
    }
}
