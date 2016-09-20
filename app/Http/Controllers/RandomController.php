<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Helpers\Helper;

class RandomController extends Controller
{
    /**
     * Picks a random number between min and max.
     *
     * @param  Request $request
     * @param  string  $number
     * @param  integer $min     The minimum number
     * @param  integer $max     The maximum number
     * @return Response
     */
    public function number(Request $request, $number, $min = 0, $max = 100)
    {
        $min = intval($min);
        $max = intval($max);

        return Helper::text(rand($min, $max));
    }
}
