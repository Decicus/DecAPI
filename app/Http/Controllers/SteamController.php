<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Controllers\Controller;

use App\Helpers\Helper;
use Syntax\SteamApi\Facades\SteamApi;

use GuzzleHttp\Exception\ClientException;

use Cache;
use Exception;
use Log;
use Redirect;
use Validator;

class SteamController extends Controller
{
    /**
     * A currency code => currency name array of available Steam currencies
     *
     * @var array
     */
    private $currencies = [
        'ae' => 'U.A.E. Dirham',
        'ar' => 'Argentine Peso',
        'az' => 'CIS - U.S. Dollar',
        'br' => 'Brazilian Real',
        'ca' => 'Canadian Dollar',
        'ch' => 'Swiss Franc',
        'cl' => 'Chilean Peso',
        'cn' => 'Chinese Yuan Renminbi',
        'co' => 'Colombian Peso',
        'cr' => 'Costa Rican Colon',
        'eu' => 'Euro',
        'hk' => 'Hong Kong Dollar',
        'id' => 'Indonesian Rupiah',
        'il' => 'Israeli New Shekel',
        'in' => 'Indian Rupee',
        'jp' => 'Japanese Yen',
        'kr' => 'South Korean Won',
        'kw' => 'Kuwaiti Dinar',
        'kz' => 'Kazakhstani Tenge',
        'mx' => 'Mexican Peso',
        'my' => 'Malaysian Ringgit',
        'no' => 'Norwegian Krone',
        'nz' => 'New Zealand Dollar',
        'pe' => 'Peruvian Nuevo Sol',
        'ph' => 'Philippine Peso',
        'pk' => 'South Asia - U.S. Dollar',
        'pl' => 'Polish Zloty',
        'qa' => 'Qatari Riyal',
        'ru' => 'Russian Ruble',
        'sa' => 'Saudi Riyal',
        'sg' => 'Singapore Dollar',
        'th' => 'Thai Baht',
        'tr' => 'Turkish Lira',
        'tw' => 'Taiwan Dollar',
        'ua' => 'Ukrainian Hryvnia',
        'uk' => 'British Pound',
        'us' => 'U.S. Dollar',
        'uy' => 'Uruguayan Peso',
        'vn' => 'Vietnamese Dong',
        'za' => 'South African Rand',
    ];

    /**
     * Returns a JSON object with "currency code" => "currency name" for available Steam currencies.
     *
     * @param  Request $request
     * @return Response
     */
    public function listCurrencies(Request $request)
    {
        $currencies = $this->currencies;
        if ($request->wantsJson()) {
            return Helper::json($currencies);
        }

        $list = "# Currency code - Currency name" . PHP_EOL;
        foreach($currencies as $code => $name) {
            $list .= $code . " - " . $name . PHP_EOL;
        }
        return Helper::text($list);
    }

    /**
     * Redirects the user to connect to the specified options on the specified game's app ID
     *
     * @param  Request $request
     * @param  int     $appId      The app ID of the game
     * @param  string  $parameters The parameters to connect with
     * @return Response
     */
    public function connect(Request $request, $appId = null, $parameters = null)
    {
        if (empty($appId)) {
            return Helper::text('App ID has to be specified.');
        }

        if (empty($parameters)) {
            return Helper::text('Parameters have to be specified.');
        }

        $format = "steam://run/%d/connect/%s";
        return Redirect::away(sprintf($format, $appId, $parameters));
    }

    /**
     * Returns a response with information about a game, specified by search.
     *
     * @param  Request $request
     * @return Response
     */
    public function gameInfoBySearch(Request $request)
    {
        $json = $request->wantsJson();
        $search = trim($request->input('search', ''));
        $cc = strtolower($request->input('cc', 'us'));
        $strict = $request->input('strict', false);
        $emptySpace = $request->input('empty', false);
        $error = null;

        if (empty($search)) {
            $error = 'Missing parameter: search';
        }

        if (empty($this->currencies[$cc])) {
            $error = 'Invalid currency code specified';
        }

        // Pass error back before making a request to API, if it's set
        if (!empty($error)) {
            if ($json) {
                return Helper::json(['error' => $error], 404);
            }

            return Helper::text($error);
        }

        $search = urlencode($search);
        $cc = strtoupper($cc); // Apparently the storefront API returns LESS data with lowercase currency code... not sure why
        $requestUrl = 'http://store.steampowered.com/api/storesearch/?term=' . $search . '&cc=' . $cc;
        $data = Helper::get($requestUrl);
        if (!isset($data['total']) || $data['total'] === 0) {

            $data = Helper::get($requestUrl); // Re-request the information, as the storefront API is sometimes 'hit or miss'.
            if (!isset($data['total']) || $data['total'] === 0) {
                $error = 'No results found';
                if ($json) {
                    return Helper::json(['error' => $error], 404);
                }

                // $emptySpace is specifically for Nightbot and others, where it will just send something 'empty' instead of an error.
                return Helper::text(($emptySpace ? PHP_EOL : $error));
            }
        }

        $game = null;
        foreach ($data['items'] as $g) {
            if (strtolower($search) === strtolower($g['name'])) {
                $game = $g;
                break;
            }
        }

        if (empty($game)) {
            $game = $data['items'][0]; // fall back to first item
        }

        if ($strict) {
            if (strtolower($search) !== strtolower($game['name'])) {
                $error = '[Strict] No matching games found';
                if ($json) {
                    return Helper::json(['error' => $error], 404);
                }

                return Helper::text($error);
            }
        }

        $url = 'http://store.steampowered.com/app/' . $game['id'] . '/';
        if ($json) {
            $game['url'] = $url;
            return Helper::json($game);
        }

        $values = [$game['name'], $url];

        if (isset($game['price'])) {
            $price = $game['price'];
            $values[] = ($price['final'] / 100) . " " . $price['currency'];
        }

        return Helper::text(implode($values, " - "));
    }

    /**
     * Retrieves the global player count for the specified app ID.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function globalPlayers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'appid' => 'required|integer|min:10',
        ]);

        if ($validator->fails()) {
            return Helper::text('Missing or invalid Steam app ID');
        }

        $appId = $request->input('appid');
        $cacheKey = sprintf('steam_global-players_%s', intval($appId));

        if (Cache::has($cacheKey)) {
            return Helper::text(Cache::get($cacheKey));
        }

        $apiUrl = sprintf('https://api.steampowered.com/ISteamUserStats/GetNumberOfCurrentPlayers/v1/?appid=%s', $appId);
        $response = Helper::get($apiUrl);

        $data = $response['response'];
        if (!isset($data['player_count']) || $data['result'] !== 1) {
            return Helper::text(sprintf('Steam API returned an invalid response for app ID: %s', $appId));
        }

        $count = $data['player_count'];
        Cache::put($cacheKey, $count, 60);
        return Helper::text($count);
    }

    /**
     * Checks the specified Steam player's hours in the specified app/game ID
     *
     * @param  Request $request
     * @param  string  $hours
     * @param  int     $playerId Player's Steam ID
     * @param  int     $appId    App/game ID
     * @param  string  $readable If the return format should be in hours (default), or a human-readable one.
     * @return Response
     */
    public function hours(Request $request, $hours = null, $playerId = null, $appId = null, $readable = null)
    {
        $playerId = $playerId ?: $request->input('id', null);
        $appId = $appId ?: $request->input('appid', null);
        $hoursFormat = $request->input('format', '%s hours');
        $readable = ($readable === 'readable' ? true : false);
        $round = intval($request->input('round', 2));

        /**
         * Censors the Steam ID in error responses, to avoid leaking the ID to the public.
         * Useful for streamers I suppose.
         */
        $censorSteamId = $request->has('censor', false);

        $hasApiKey = $request->has('key');
        $customApiKey = $request->input('key', null);
        if ($hasApiKey) {
            config(['steam-api.steamApiKey' => $customApiKey]);
        }

        if (empty($playerId)) {
            return Helper::text('A Steam ID has to be specified.');
        }

        if (empty($appId)) {
            return Helper::text('An app/game ID has to be specified.');
        }

        try {
            $user = SteamApi::user($playerId);
            $player = SteamApi::Player($playerId);

            $visibility = $user->GetPlayerSummaries()[0]->communityVisibilityState;
            if ($visibility !== 3) {
                return Helper::text('Cannot retrieve player information from a private/friends-only profile.');
            }

            $games = $player->GetOwnedGames(true, true, [
                $appId
            ]);

            if (empty($games)) {
                return Helper::text('Invalid app/game ID specified.');
            }

            $game = $games->first();

            if (empty($game)) {
                return Helper::text('The player does not seem to own the specified game, or the Steam privacy setting for "Game details" is not set to public.');
            }

            if ($readable === true) {
                $readableTime = $game->playtimeForeverReadable;
                return Helper::text($readableTime);
            }

            $hours = round($game->playtimeForever / 60, $round);
            return Helper::text(sprintf($hoursFormat, $hours));
        }
        catch (Exception $e) {
            if ($censorSteamId) {
                $playerId = 'X';
            }

            $errorFormat = 'An error occurred retrieving hours for Steam ID: %s with app ID: %s%s';

            $errorApiKey = '';
            if ($hasApiKey) {
                $errorApiKey = ' - Using custom Steam API key.';
            }

            /**
             * Handle client exceptions differently.
             * Mainly for 403 errors with custom API keys.
             */
            $parentEx = $e->getPrevious();
            if ($parentEx !== null && $parentEx instanceof ClientException) {
                $response = $parentEx->getResponse();

                $errorFormat = 'Error from Steam API: %s %s - Steam ID: %s - App ID: %s%s';
                $statusCode = $response->getStatusCode();
                $reasonPhrase = $response->getReasonPhrase();

                $message = sprintf($errorFormat, $statusCode, $reasonPhrase, $playerId, $appId, $errorApiKey);
                return Helper::text($message);
            }

            return Helper::text(sprintf($errorFormat, $playerId, $appId, $errorApiKey));
        }
    }

    /**
     * Retrieves the current gameserver IP for the specified user.
     *
     * @param  Request $request
     * @param  string  $serverIp
     * @param  int     $id       The player's Steam ID
     * @return Response
     */
    public function serverIp(Request $request, $serverIp = null, $id = null)
    {
        $id = $id ?: $request->input('id', null);

        if (empty($id)) {
            return Helper::text('You have to specify a Steam ID.');
        }

        $hasApiKey = $request->has('key');
        $customApiKey = $request->input('key', null);
        if ($hasApiKey) {
            config(['steam-api.steamApiKey' => $customApiKey]);
        }

        $key = config('steam-api.steamApiKey');
        $url = sprintf('https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=%s&steamids=%s', $key, $id);

        try {
            // For whatever reason, the library used for hours() does not support retrieving the gameserverip
            // value from the player array. Which is why I have to make my own requests for this.
            $data = Helper::get($url);
            if ($data === null) {
                return Helper::text('An invalid API key was specified.');
            }
            $response = $data['response'];

            if (empty($response['players'])) {
                return Helper::text('An invalid Steam ID was specified.');
            }

            $player = $response['players'][0];

            if (empty($player['gameserverip'])) {
                return Helper::text('The specified player is currently not connected to any valid gameserver, or this data is not public.');
            }

            return Helper::text($player['gameserverip']);
        } catch (Exception $e) {
            Log::error('Error occurred in /steam/server_ip:');
            Log::error($e);

            $errorFormat = 'An error occurred retrieving gameserver IP for Steam ID: %s%s';
            return Helper::text(sprintf($errorFormat, $id, ($hasApiKey ? ' - Using custom Steam API key.' : '')));
        }
    }
}
