<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Helpers\Helper;
use Exception;

class MathController extends Controller
{
    /**
     * Base URL for math.js API.
     *
     * @var string
     */
    private $mathBaseUrl = 'http://api.mathjs.org/v1/?expr=';

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

        try {
            $result = Helper::get($this->mathBaseUrl . urlencode($exp), [], false);

            if ($request->exists('round') === true) {
                $result = round($result, $round);
            }

            return Helper::text($result);
        } catch (RuntimeException $e) {
            return Helper::text('An error occurred evaluating: ' . $exp);
        }
    }
}
