<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use GuzzleHttp\Client;
use Vinelab\Rss\Rss;
use App\Helpers\Helper;
use GameQ\GameQ;
use Log;
use Searchy;

use App\IzurviveLocation as Location;
use App\IzurviveLocationSpelling as Spelling;


class DayZController extends Controller
{
    /**
     * The master server API URL.
     *
     * @var string
     */
    private $masterServerUrl = 'http://api.steampowered.com/IGameServersService/GetServerList/v1/?filter=\gamedir\dayz%s&limit=%d&key=%s';

    /**
     * The base API endpoint
     *
     * @return Response
     */
    public function base()
    {
        $base = url('/dayz/');
        $urls = [
            'endpoints' => [
                $base . '/izurvive',
                $base . '/players',
                $base . '/random-server',
                $base . '/status-report',
                $base . '/steam-status-report'
            ]
        ];

        return Helper::json($urls);
    }

    /**
     * Maps location names/searches to their izurvive.com locations
     *
     * @param  Request $request
     * @return Response
     */
    public function izurvive(Request $request)
    {
        $prefix = 'https://www.izurvive.com/';
        $maxResults = intval($request->input('max_results', 1));
        $separator = $request->input('separator', ' | ');
        $zoom = intval($request->input('zoom_level', 7));

        if ($request->exists('list')) {
            $locations = [];
            $spellings = [];

            foreach (Location::all() as $location) {
                $name = $location->name_en;
                $locations[$name] = sprintf('#c=%s;%s', intval($location->latitude), intval($location->longitude));
                $spellings[$name] = $location
                                    ->spellings
                                    ->pluck('spelling')
                                    ->all();
            }

            if ($request->wantsJson()) {
                $data = [
                    'url_template' => $prefix . '{location}',
                    'locations' => $locations,
                    'spellings' => $spellings,
                ];

                return Helper::json($data);
            }

            $data = [
                'list' => $locations,
                'spellings' => $spellings,
                'prefix' => $prefix,
                'page' => 'Available Search Locations',
            ];

            return view('dayz.izurvive', $data);
        }

        $search = $request->input('search', null);
        if (empty($search)) {
            return Helper::text('Please specify ?search= or see a list of available locations: ' . route('dayz.izurvive') . '?list');
        }

        $search = urldecode(trim($search));
        $results = Searchy::izurvive_location_spellings('location_id', 'spelling')
                ->query($search)
                ->get();

        if ($results->isEmpty()) {
            return Helper::text('No results found for search: ' . $search);
        }

        $results = Spelling::hydrate($results->all());
        $spellingResults = $results->take($maxResults);

        $locations = [];
        foreach ($spellingResults as $spelling) {
            $location = $spelling->location;
            $url = sprintf('%s - %s#c=%d;%d;%d', $location->name_en, $prefix, intval($location->latitude), intval($location->longitude), $zoom);
            $locations[] = str_replace(';', '%3B', $url);
        }

        return Helper::text(implode($separator, $locations));
    }

    /**
     * Queries DayZ servers and returns their current player count
     *
     * @param  Request $request
     * @return Response
     */
    public function players(Request $request)
    {
        $ip = $request->input('ip', null);
        $port = $request->input('port', null);
        $query_port = $request->input('query', null);

        if (empty($ip) || empty($port)) {
            return Helper::text('[Error: Please specify "ip" AND "port".]');
        }

        $query_port = (empty($query_port) ? intval($port) : intval($query_port));
        $address = $ip . ':' . $port;

        $query = new GameQ();
        $query->addServer([
            'type' => 'dayz',
            'host' => $address,
            'options' => [
                'query_port' => $query_port,
            ],
        ]);
        $query->setOption('timeout', 30);

        $result = $query->process();
        if (empty($result[$address])) {
            Log::error('Unable to query gameserver address: ' . $address);
            return Helper::text('[Error: Unable to query server.]');
        }

        $result = $result[$address];
        if (!isset($result['num_players'], $result['max_players'])) {
            return Helper::text('[Error: Unable to retrieve player count.]');
        }

        return Helper::text($result['num_players'] . '/' . $result['max_players']);
    }

    /**
     * Retrieves a random DayZ server.
     *
     * @param Request $request
     * @return Response
     */
    public function randomServer(Request $request)
    {
        $results = $request->input('results', 'ip');
        $filter = '\name_match\*';
        $key = env('STEAM_API_KEY');
        $url = sprintf($this->masterServerUrl, $filter, 5000, $key);

        $client = new Client;
        $response = $client->request('GET', $url, [
            'http_errors' => false
        ]);

        $body = json_decode(utf8_encode($response->getBody()), true);

        if (empty($body['response']['servers']) || count($body['response']['servers']) <= 0) {
            return Helper::text('An error occurred while retrieving server info.');
        }

        $servers = $body['response']['servers'];
        $count = count($servers);
        $serv = $servers[random_int(0, $count - 1)];
        $splitIp = explode(':', $serv['addr']);
        $options = [
            'name' => $serv['name'],
            'ip' => $splitIp[0] . ':' . $serv['gameport'],
            'players' => $serv['players'] . '/' . $serv['max_players']
        ];

        $format = '%s';
        $results = explode(',', $results);
        $text = [];

        foreach ($results as $result) {
            if (!empty($options[$result])) {
                $text[] = $options[$result];
            }
        }

        if (empty($text)) {
            $text = [
                $options['ip']
            ];
        }

        return Helper::text(implode(' - ', $text));
    }

    /**
     * Retrieves the latest DayZ "status report" from their blog.
     *
     * @return Response
     */
    public function statusReport()
    {
        $client = new Client;
        $result = $client->request('GET', 'https://dayz.com/loadNews?page=1', [
            'http_errors' => false
        ]);

        $data = json_decode($result->getBody(), true);

        foreach ($data as $post) {
            $title = $post['title'];
            if (strpos(strtolower($title), 'status report') !== false) {
                return Helper::text($title . ' - https://dayz.com/blog/' . $post['slug']);
            }
        }

        return Helper::text('No status reports found.');
    }

    /**
     * Retrieves the latest DayZ status report posted to Steam news.
     *
     * @return Response
     */
    public function steamStatusReport()
    {
        $rss = new Rss();
        $feed = $rss->feed('https://steamcommunity.com/games/221100/rss/');

        $articles = $feed->articles();

        foreach ($articles as $article) {
            $title = $article->title;
            if (strpos(strtolower($title), 'status report') !== false) {
                return Helper::text($title . ' - ' . $article->link);
            }
        }

        return Helper::text('No status reports found.');
    }
}
