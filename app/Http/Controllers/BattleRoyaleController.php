<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Helpers\Helper;

class BattleRoyaleController extends Controller
{
    /**
     * The base API URL for Battle Royale stats
     *
     * @var string
     */
    public $baseUrl = 'https://leaderboard.battleroyalegames.com/api';

    /**
     * Retrieves the information from the specified endpoint in the Battle Royale API
     *
     * @param  string $endpoint Name of endpoint
     * @return Response
     */
    public function get($endpoint = '')
    {
        return Helper::get($this->baseUrl . $endpoint);
    }

    /**
     * Returns the player information retrieved from the API.
     *
     * @param  Request $request
     * @param  string  $id      The Steam ID or BR unique ID of the player.
     * @param  string  $type    Retrieve from regular or hardcore stats.
     * @return Response
     */
    public function player(Request $request, $id = null, $type = 'regular')
    {
        if (empty($id)) {
            return Helper::text('You need to specify a player ID.');
        }

        /**
         * Default values to retrieve from the player summary
         *
         * @var array
         */
        $default = [
            'name',
            'URL',
            'rank',
            'kills',
            'wins',
            'losses'
        ];

        /**
         * URL-specified options that override the $default array.
         *
         * @var array
         */
        $options = [];
        if ($request->has('options')) {
            $options = explode(",", $request->input('options'));
        } else {
            $options = $default;
        }

        $separator = ' ' . $request->input('separator', '|') . ' ';

        $data = $this->get('/player/' . $id);

        if (!empty($data['message'])) {
            return Helper::text($data['message']);
        }

        if (empty($data[$type . '_summary'])) {
            return Helper::text('No data available for this player.');
        }

        $summary['name'] = $data['name'];
        $summary['URL'] = $data['profile_url'];
        $summary['type'] = ucfirst($type);
        $summary = array_merge($summary, $data[$type . '_summary']);
        // dirty hack to override rank value
        $summary['rank'] = $summary['rank']['rank'];

        /**
         * Dumps a list of 'default' options if none are specified.
         */
        if ($request->input('options') === 'default') {
            $output = 'List of default options: ' . PHP_EOL;
            $output .= '- ' . implode(PHP_EOL . '- ', $default);
            return Helper::text($output);
        }

        /**
         * Dumps a list of available options for the user to specify.
         */
        if ($request->input('options') === 'list') {
            $output = 'List of available options (comma-separated & case-sensitive):' . PHP_EOL;
            $output .= '- ' . implode(PHP_EOL . '- ', array_keys($summary));
            return Helper::text($output);
        }

        $output = [];
        foreach ($options as $opt) {
            if (isset($summary[$opt])) {
                $output[] = ucfirst(str_replace('_', ' ', $opt)) . ': ' . $summary[$opt];
            }
        }

        if (empty($output)) {
            return Helper::text('No valid options where specified. Visit '. route('br.player.summary', [$id, $type]) . '?options=list');
        }

        return Helper::text(implode($separator, $output));
    }
}
