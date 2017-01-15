<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Helpers\Helper;
use Math;

class MathController extends Controller
{
    /**
     * Evaluates a math expression.
     *
     * @param  Request $request
     * @return Response
     */
    public function eval(Request $request)
    {
        $exp = $request->input('exp', null);

        if (empty($exp)) {
            return Helper::text('A math expression (exp) has to be specified.');
        }

        $parser = new Math\Parser;
        return Helper::text($parser->evaluate($exp));
    }
}
