<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use GuzzleHttp\Client;
use Vinelab\Rss\Rss;
use App\Helpers\Helper;
use GameQ\GameQ;

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
        // Location names => coordinates + zoom level
        // config/dayz.php
        $locations = config('dayz.izurvive');
        $prefix = 'https://www.izurvive.com/';

        if ($request->exists('list')) {
            if ($request->wantsJson()) {
                $data = [
                    'url_template' => $prefix . '{location}',
                    'locations' => $locations
                ];

                return Helper::json($data);
            }

            $data = [
                'list' => $locations,
                'prefix' => $prefix,
                'page' => 'Available Search Locations'
            ];

            return view('shared.list', $data);
        }

        $search = $request->input('search', null);
        if (empty($search)) {
            return Helper::text('Please specify ?search= or see a list of available locations: ' . route('dayz.izurvive') . '?list');
        }

        $search = urldecode(trim($search));
        $names = array_keys($locations);

        $check = preg_grep('/(' . $search . ')/i', $names);

        if (empty($check)) {
            return Helper::text('No results found.');
        }

        $name = array_values($check)[0];
        return Helper::text($name . ' - ' . $prefix . str_replace(';', '%3B', $locations[$name]));
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
                'query_port' => $query_port
            ]
        ]);
        $query->setOption('timeout', 30);

        $result = $query->process();
        if (empty($result[$address])) {
            return Helper::text('[Error: Unable to query server.]');
        }

        $result = $result[$address];
        if (empty($result['num_players']) || empty($result['max_players'])) {
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
