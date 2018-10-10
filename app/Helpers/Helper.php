<?php

namespace App\Helpers;

use GuzzleHttp\Client;

class Helper
{
    /**
     * Get human readable time difference between 2 dates
     *
     * Return difference between 2 dates in year, month, hour, minute or second
     * The $precision caps the number of time units used: for instance if
     * $time1 - $time2 = 3 days, 4 hours, 12 minutes, 5 seconds
     * - with precision = 1 : 3 days
     * - with precision = 2 : 3 days, 4 hours
     * - with precision = 3 : 3 days, 4 hours, 12 minutes
     *
     * From: http://www.if-not-true-then-false.com/2010/php-calculate-real-differences-between-two-dates-or-timestamps/
     * Code snippet credit: https://gist.github.com/ozh/8169202
     *
     * Modified to support localization strings in Laravel.
     *
     * @param mixed $time1 a time (string or timestamp)
     * @param mixed $time2 a time (string or timestamp)
     * @param integer $precision Optional precision
     * @return string time difference
     */
    public static function getDateDiff($time1, $time2, $precision = 2)
    {
        if ($precision === 0) {
            $precision = 2;
        }

        // If not numeric then convert timestamps
        if(!is_int($time1)) {
            $time1 = strtotime($time1);
        }
        if(!is_int($time2)) {
            $time2 = strtotime($time2);
        }
        // If time1 > time2 then swap the 2 values
        if($time1 > $time2) {
            list($time1, $time2) = array($time2, $time1);
        }
        // Set up intervals and diffs arrays
        $intervals = array('year', 'month', 'week', 'day', 'hour', 'minute', 'second');
        $diffs = array();
        foreach($intervals as $interval) {
            // Create temp time from time1 and interval
            $ttime = strtotime('+1 ' . $interval, $time1);
            // Set initial values
            $add = 1;
            $looped = 0;
            // Loop until temp time is smaller than time2
            while ($time2 >= $ttime) {
                // Create new temp time from time1 and interval
                $add++;
                $ttime = strtotime("+" . $add . " " . $interval, $time1);
                $looped++;
            }
            $time1 = strtotime("+" . $looped . " " . $interval, $time1);
            $diffs[$interval] = $looped;
        }
        $count = 0;
        $times = array();
        foreach($diffs as $interval => $value) {
            // Break if we have needed precission
            if($count >= $precision) {
                break;
            }
            // Add value and interval if value is bigger than 0
            //if($value > 0) {
                // Add value and interval to times array
                $times[] = trans_choice('time.' . $interval, $value, [
                    'value' => $value,
                ]);

                $count++;
            //}
        }
        // Return string with times
        return implode(", ", $times);
    }

    /**
     * Retrieves information from the specified URL and converts it from JSON.
     *
     * @param  string   $url        The request url
     * @param  array    $headers    HTTP headers to send with the request
     * @param  boolean  $isJson     Set to 'false' if the expected request result is not JSON (the raw result will be returned)
     * @return array
     */
    public static function get($url = '', $headers = [], $isJson = true)
    {
        $settings = [];
        $settings['http_errors'] = false;
        $settings['headers'] = $headers;

        if (empty($settings['headers']['User-Agent'])) {
            $settings['headers']['User-Agent'] = env('DECAPI_USER_AGENT', 'DecAPI/1.0.0 (https://github.com/Decicus/DecAPI)');
        }

        $client = new Client();
        $result = $client->request('GET', $url, $settings);
        return ($isJson ? json_decode($result->getBody(), true) : $result->getBody());
    }

    /**
     * Returns a JSON response with set headers
     *
     * @param  array  $data
     * @param  integer $code    HTTP status code
     * @param  array  $headers HTTP headers
     * @return response
     */
    public static function json($data = [], $code = 200, $headers = [])
    {
        $headers['Content-Type'] = 'application/json';
        $headers['Access-Control-Allow-Origin'] = '*';

        return \Response::json($data, $code)->withHeaders($headers);
    }

    /**
     * Returns a plaintext response with set headers
     *
     * @param  string  $text    Text to send
     * @param  integer $code    HTTP status code
     * @param  array   $headers HTTP headers
     * @return response
     */
    public static function text($text = '', $code = 200, $headers = [])
    {
        $headers['Content-Type'] = 'text/plain';
        $headers['Access-Control-Allow-Origin'] = '*';

        return response($text, $code)->withHeaders($headers);
    }

    /**
     * Redirects the user back to the home view with a message ID.
     *
     * @param  string $id The message ID to redirect back with.
     * @return Response
     */
    public static function message($id = '')
    {
        return redirect('/?message=' . $id);
    }
}
