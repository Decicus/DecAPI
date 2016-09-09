<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Helpers\Helper;
use Steam;
use Exception;

class SteamController extends Controller
{
    /**
     * A currency code => currency name array of available Steam currencies
     *
     * @var array
     */
    private $currencies = [
        "ae" => "UAE Dirham",
        "az" => "CIS - U.S. Dollar",
        "br" => "Brazilian Real",
        "ca" => "Canadian Dollar",
        "ch" => "Swiss Franc",
        "cl" => "Chilean Peso",
        "cn" => "Chinese Yuan",
        "co" => "Colombian Peso",
        "eu" => "Euro",
        "hk" => "Hong Kong Dollar",
        "id" => "Indonesian Rupiah",
        "in" => "Indian Rupee",
        "jp" => "Japanese Yen",
        "kr" => "South Korean Won",
        "mx" => "Mexican Peso",
        "my" => "Malaysian Ringgit",
        "no" => "Norwegian Krone",
        "nz" => "New Zealand Dollar",
        "pe" => "Peruvian Nuevo Sol",
        "ph" => "Philippine Peso",
        "ru" => "Russian Ruble",
        "sa" => "Saudi Riyal",
        "sg" => "Singapore Dollar",
        "th" => "Thai Baht",
        "tr" => "Turkish Lira",
        "tw" => "Taiwan Dollar",
        "uk" => "British Pound",
        "us" => "U.S. Dollar",
        "za" => "South African Rand"
    ];

    /**
     * Array of standard HTTP headers to return back to the client
     *
     * @var array
     */
    private $headers = [
        'Access-Control-Allow-Origin' => '*'
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
        $readable = ($readable === 'readable' ? true : false);
        $round = intval($request->input('round', 2));

        if ($request->has('key')) {
            config(['steam-api.steamApiKey' => $request->input('key')]);
        }

        if (empty($playerId)) {
            return Helper::text('A Steam ID has to be specified.');
        }

        if (empty($appId)) {
            return Helper::text('An app/game ID has to be specified.');
        }

        try {
            $user = Steam::user($playerId);
            $player = Steam::Player($playerId);
            if ($user->GetPlayerSummaries()[0]->communityVisibilityState === 1) {
                return Helper::text('Cannot retrieve player information from a private/friends-only profile.');
            }

            $games = $player->GetOwnedGames(true, false, [
                $appId
            ]);

            if (empty($games)) {
                return Helper::text('Invalid app/game ID specified.');
            }

            $game = $games->first();

            if ($readable === true) {
                return Helper::text($game->playtimeForeverReadable);
            }

            $hours = round($game->playtimeForever / 60, $round);
            return Helper::text($hours . ' hours');
        } catch (Exception $e) {
            return Helper::text($e->getMessage());
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

        if ($request->has('key')) {
            config(['steam-api.steamApiKey' => $request->input('key')]);
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
            return Helper::text($e->getMessage());
        }
    }
}
