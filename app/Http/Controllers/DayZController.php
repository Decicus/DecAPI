<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use GuzzleHttp\Client;
use Vinelab\Rss\Rss;

class DayZController extends Controller
{
    /**
     * Returns a JSON response with set headers
     *
     * @param  array  $data
     * @param  integer $code    HTTP status code
     * @param  array  $headers HTTP headers
     * @return response
     */
    protected function json($data = [], $code = 200, $headers = [])
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
    protected function text($text = '', $code = 200, $headers = [])
    {
        $headers['Content-Type'] = 'text/plain';
        $headers['Access-Control-Allow-Origin'] = '*';

        return response($text)->withHeaders($headers);
    }

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
                $base . '/status-report',
                $base . '/steam-status-report'
            ]
        ];

        return $this->json($urls);
    }

    public function izurvive(Request $request)
    {
        // Location names => coordinates + zoom level
        $locations = [
            'Abandoned Mil Base' => '#c=82;0;7',
            'Altar' => '#c=26;4;7',
            'Balota' => '#c=-77;-77;7',
            'Balota Airfield' => '#c=-78;-66;7',
            'Baranchik' => '#c=-37;47;7',
            'Bashnya' => '#c=64;-86;7',
            'Bay Mutnaya' => '#c=-79;-52;7',
            'Bay Nizhnaya' => '#c=12;109;7',
            'Bay Tikhaya' => '#c=-78;-151;7',
            'Bay Zelenaya' => '#c=-74;72;7',
            'Belaya Polana' => '#c=83;137;7',
            'Berezhki' => '#c=79;156;7',
            'Berezino' => '#c=25;90;7',
            'Black Forest' => '#c=-9;24;7',
            'Black Lake' => '#c=67;123;7',
            'Black Mountain' => '#c=67;51;7',
            'Blunt Rocks' => '#c=66;112;7',
            'Bor' => '#c=-67;-103;7',
            'Cap Golova' => '#c=-77;7;7',
            'Cernaya Polana' => '#c=79;91;7',
            'Chapaevsk' => '#c=-77;-51;7',
            'Chernogorsk' => '#c=-77;-26;7',
            'Chyornaya Bay' => '#c=-74;-7;7',
            'Devils Castle' => '#c=61;-22;7',
            'Dichina' => '#c=-2;-73;7',
            'Dobroe' => '#c=83;113;7',
            'Dobryy' => '#c=-74;56;7',
            'Dolina' => '#c=-29;74;7',
            'Drakon' => '#c=-77;72;7',
            'Drozhino' => '#c=-56;-103;7',
            'Dubina' => '#c=11;69;7',
            'Dubky' => '#c=-70;-29;7',
            'Dubrovka' => '#c=39;56;7',
            'Elektrozavodsk' => '#c=-79;52;7',
            'Factory (cherno)' => '#c=-76;-34;7',
            'Factory Coast' => '#c=-20;116;7',
            'Factory Inland' => '#c=-12;82;7',
            'Gorka' => '#c=19;38;7',
            'Green Mountain' => '#c=-41;-95;7',
            'Grishino' => '#c=47;-44;7',
            'Grozovoy Pass' => '#c=83;-103;7',
            'Guba' => '#c=75;144;7',
            'Guglovo' => '#c=-28;12;7',
            'Gvozdno' => '#c=66;14;7',
            'Kabanino' => '#c=15;-58;7',
            'Kamenka' => '#c=-78;-137;7',
            'Kamensk' => '#c=81;-29;7',
            'Kamyshovo' => '#c=-70;92;7',
            'Karmanovka' => '#c=82;103;7',
            'Khelm' => '#c=54;98;7',
            'Klen' => '#c=61;80;7',
            'Komarovo' => '#c=-77;-97;7',
            'Kopyto' => '#c=-67;-3;7',
            'Kozlovka' => '#c=-60;-79;7',
            'Krasnoe' => '#c=83;-34;7',
            'Krasnostav' => '#c=69;72;7',
            'Krutoy Cap' => '#c=-66;122;7',
            'Kumyrna' => '#c=-40;6;7',
            'Kurgan' => '#c=-53;-101;7',
            'Kustryk' => '#c=-54;-68;7',
            'Lesnoy Khrebet' => '#c=-4;2;7',
            'Little Hill' => '#c=-56;-24;7',
            'Lopatino' => '#c=42;-116;7',
            'Lumber Mill' => '#c=36;108;7',
            'Malinovka' => '#c=-9;64;7',
            'Mamino' => '#c=75;1;7',
            'Mogilevka' => '#c=-54;-7;7',
            'Msta' => '#c=-49;76;7',
            'Myshkino' => '#c=-14;-133;7',
            'Myshkino Tents' => '#c=-17;-154;7',
            'NEAF' => '#c=72;94;7',
            'NWAF' => '#c=45;-74;7',
            'Nadezhdino' => '#c=-59;-46;7',
            'Nagornoe' => '#c=82;32;7',
            'Nizhnoye' => '#c=2;110;7',
            'Novaya Petrovka' => '#c=75;-94;7',
            'Novodmitrovsk' => '#c=81;73;7',
            'Novoselky' => '#c=-72;-41;7',
            'Novy Lug' => '#c=59;33;7',
            'Novy Sobor' => '#c=-5;-19;7',
            'Old Fields' => '#c=28;-26;7',
            'Olsha' => '#c=74;122;7',
            'Orlovets' => '#c=-16;94;7',
            'Ostry' => '#c=73;63;7',
            'Otmel' => '#c=-73;84;7',
            'Ozerko' => '#c=-62;-25;7',
            'Pass Oreshka' => '#c=-41;43;7',
            'Pass Sosnovy' => '#c=-29;-118;7',
            'Pavlovo' => '#c=-68;-140;7',
            'Pavlovo Mil Base' => '#c=-71;-131;7',
            'Pik Kozlova' => '#c=-75;19;7',
            'Pogorevka' => '#c=-33;-79;7',
            'Polana' => '#c=2;62;7',
            'Polesovo' => '#c=77;-47;7',
            'Power Plant' => '#c=-76;50;7',
            'Prigorodki' => '#c=-71;0;7',
            'Prison Island' => '#c=-82;-117;7',
            'Prud' => '#c=29;-31;7',
            'Pulkovo' => '#c=-46;-67;7',
            'Pusta' => '#c=-67;27;7',
            'Pustoshka' => '#c=-1;-109;7',
            'Pustoy Khrebet' => '#c=-46;61;7',
            'Quarry' => '#c=-39;122;7',
            'Ratnoe' => '#c=72;-39;7',
            'Rify' => '#c=59;128;7',
            'Rog' => '#c=-64;74;7',
            'Rogovo' => '#c=-25;-72;7',
            'Severograd' => '#c=72;-1;7',
            'Shakhovka' => '#c=-30;38;7',
            'Sinistok' => '#c=67;-148;7',
            'Skalisty Island' => '#c=-74;128;7',
            'Skalisty Proliv' => '#c=-69;119;7',
            'Smirnovo' => '#c=83;76;7',
            'Solnichniy' => '#c=-35;122;7',
            'Sosnovka' => '#c=-34;-122;7',
            'Staroye' => '#c=-49;49;7',
            'Stary Sobor' => '#c=-4;-41;7',
            'Stary Yar' => '#c=83;-67;7',
            'Svergino' => '#c=79;33;7',
            'Svetlojarsk' => '#c=76;133;7',
            'Three Valleys' => '#c=-49;112;7',
            'Tisy' => '#c=82;-102;7',
            'Tisy Military Base' => '#c=80;-142;7',
            'Toploniki' => '#c=70;-116;7',
            'Topolka Dam' => '#c=-70;52;7',
            'Troitskoe' => '#c=78;-9;7',
            'Tulga' => '#c=-62;109;7',
            'Turovo' => '#c=80;123;7',
            'Vavilovo' => '#c=57;-125;7',
            'Veresnik' => '#c=2;-79;7',
            'Veresnik Mil Base' => '#c=8;-76;7',
            'Vybor' => '#c=21;-92;7',
            'Vyshnaya Dubrovka' => '#c=49;44;7',
            'Vyshnoye' => '#c=-39;-31;7',
            'Vysoky Kamen' => '#c=-63;21;7',
            'Vysota' => '#c=-72;-29;7',
            'Willow Lake' => '#c=63;120;7',
            'Windy Mountain' => '#c=-65;-90;7',
            'Zaprudnoe' => '#c=75;-76;7',
            'Zelenogorsk' => '#c=-51;-117;7',
            'Zub' => '#c=-47;-31;7'
        ];

        $search = $request->input('search', null);
        if (empty($search)) {
            return $this->text('Please specify ?search=');
        }

        $search = urldecode(trim($search));
        $names = array_keys($locations);

        $check = preg_grep('/(' . $search . ')/i', $names);

        if (empty($check)) {
            return $this->text('No results found.');
        }

        $name = array_values($check)[0];
        return $name . ' - https://www.izurvive.com/' . str_replace(';', '%3B', $locations[$name]);
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
                return $this->text($title . ' - https://dayz.com/blog/' . $post['slug']);
            }
        }

        return $this->text('No status reports found.');
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
                return $this->text($title . ' - ' . $article->link);
            }
        }

        return $this->text('No status reports found.');
    }
}
