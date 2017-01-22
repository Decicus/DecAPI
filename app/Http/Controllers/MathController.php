<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Helpers\Helper;
use PHPMathParser;
use RuntimeException;

class MathController extends Controller
{
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
            $parser = new PHPMathParser\Math;
            $result = $parser->evaluate($exp);

            if ($request->exists('round') === true) {
                $result = round($result, $round);
            }

            return Helper::text($result);
        } catch (RuntimeException $e) {
            return Helper::text($e->getMessage());
        }
    }
}
