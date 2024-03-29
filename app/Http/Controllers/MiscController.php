<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Exceptions\CurrencyApiException;
use App\Helpers\Helper;
use App\Repositories\CurrencyApiRepository;
use Carbon\Carbon;
use DateTimeZone;


class MiscController extends Controller
{
    /**
     * @var App\Repositories\CurrencyApiRepository
     */
    private $currencyApi;

    public function __construct(CurrencyApiRepository $currencyApi)
    {
        $this->currencyApi = $currencyApi;
    }

    /**
     * Converts currency based on parameters passed.
     *
     * @param  Request $request
     * @return Response
     */
    public function currency(Request $request)
    {
        $value = $request->input('value', null);
        $from = $request->input('from', null);
        $to = $request->input('to', null);
        $round = intval($request->input('round', 2));

        $listUrl = route('misc.currency', 'currency') . '?list';
        if ($request->exists('list')) {
            $currencies = $this->currencyApi->getCurrencies();
            $currencies = array_map(
                function($code) {
                    return strtoupper($code);
                },
                array_keys($currencies['currencies'])
            );

            return Helper::text('Available currencies: ' . implode(', ', $currencies));
        }

        if (empty($value)) {
            return Helper::text('The "value" parameter has to be specified');
        }

        if (empty($from)) {
            return Helper::text('The "from" parameter has to be specified');
        }

        if (empty($to)) {
            return Helper::text('The "to" parameter has to be specified');
        }

        $value = floatval(str_replace(',', '', $value));
        if ((int) $value === 0) {
            $value = 1;
        }

        $fromValid = $this->currencyApi->isValidCurrency($from);
        if (!$fromValid) {
            return Helper::text(sprintf('Invalid "from" currency specified (%s) - Available currencies can be found here: %s', $from, $listUrl));
        }

        $toValid = $this->currencyApi->isValidCurrency($to);
        if (!$toValid) {
            return Helper::text(sprintf('Invalid "to" currency specified (%s) - Available currencies can be found here: %s', $to, $listUrl));
        }

        try {
            $convert = $this->currencyApi->convert($from, $to, $value, $round);
            return Helper::text($convert);
        } catch (CurrencyApiException $e) {
            return Helper::text(sprintf('An error occurred while converting the currency: %s', $e->getMessage()));
        }
    }

    /**
     * Display the current time in the specified timezone.
     *
     * @param  Request $request
     * @return Response
     */
    public function time(Request $request)
    {
        $format = $request->input('format', 'h:i:s A T');
        $tz = $request->input('timezone', null);
        $timezones = DateTimeZone::listIdentifiers();
        if (empty($tz)) {
            return Helper::text(sprintf('-- Parameter `timezone` needs to be specified - Available timezones can be found here: %s', route('misc.timezones')));
        }

        if (!in_array($tz, $timezones)) {
            return Helper::text(sprintf('-- Invalid timezone specified ("%s") - Available timezones can be found here: %s', $tz, route('misc.timezones')));
        }

        $time = Carbon::now()
                ->tz($tz)
                ->format($format);

        return Helper::text($time);
    }

    /**
     * Displays the time difference between two specified times.
     * Similar to the Twitch `followage` endpoint.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return Response
     */
    public function timeDifference(Request $request)
    {
        $first = $request->input('first', null);
        $second = $request->input('second', null);
        $precision = (int) $request->input('precision', 7);

        if (empty($first)) {
            return Helper::text('The `first` parameter has to be specified.');
        }

        $first = strtotime($first);

        if (empty($second)) {
            $second = time();
        }
        else {
            $second = strtotime($second);
        }

        $diff = Helper::getDateDiff($first, $second, $precision);

        return Helper::text($diff);
    }

    /**
     * Lists the supported timezones.
     *
     * @param Request $request
     * @return Response
     */
    public function timezones(Request $request)
    {
        $timezones = DateTimeZone::listIdentifiers();

        if ($request->wantsJson()) {
            return Helper::json($timezones);
        }

        return Helper::text(implode(PHP_EOL, $timezones));
    }
}
