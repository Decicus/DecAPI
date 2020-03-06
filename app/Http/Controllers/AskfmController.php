<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App;
use Carbon\Carbon;

use GuzzleHttp\Client as HttpClient;
use Symfony\Component\DomCrawler\Crawler;

class AskfmController extends Controller
{
    public function rss(Request $request, $user = null)
    {
        $user = $user ?: $request->input('user', null);
        $feed = App::make('feed');

        if (empty($user)) {
            $feed->title = 'Ask.fm - RSS Feed';
            $feed->description = 'RSS feed covering answers of users on Ask.fm';
            $date = new Carbon();
            $msg = 'User has to be specified';
            $feed->add($msg, $msg, url($request->fullUrl()), $date->toAtomString(), $msg, $msg);
            return $feed->render('atom');
        }

        $httpClient = new HttpClient;

        $settings = [
            'headers' => [
                'User-Agent' => env('DECAPI_USER_AGENT', ''),
            ],
            'http_errors' => false,
        ];

        $httpRequest = $httpClient->request('GET', 'https://ask.fm/' . $user);
        $body = (string) $httpRequest->getBody();

        $dom = new Crawler($body);
        $answers = $dom->filter('.streamItem.streamItem-answer');

        if (count($answers) === 0) {
            $feed->title = 'Ask.fm - RSS Feed';
            $feed->description = 'RSS feed covering answers of users on Ask.fm';
            $date = new Carbon();
            $msg = sprintf('User %s has not answered any questions.', $user);
            $feed->add($msg, $msg, url($request->fullUrl()), $date->toAtomString(), $msg, $msg);
            return $feed->render('atom');
        }

        $feed->title = 'Ask.fm - ' . $user;
        $feed->description = 'RSS feed showing answers of Ask.fm questions asked to: ' . $user;
        $feed->link = $request->fullUrl();
        $feed->setDateFormat('datetime');
        $feed->lang = 'en';

        $ageStr = '.streamItem_meta';
        $date = $answers->first()->filter($ageStr)->attr('title');

        $feed->pubdate = $date;

        $answers->each(function(Crawler $a, $i) use($feed, $ageStr, $user) {
            $itemAge = $a->filter($ageStr);

            $question = $a->filter('.streamItem_header h2')->text();
            // Remove "View more" text at the end
            $answer = substr($a->filter('.streamItem_content')->text(), 0, -9);
            $link = "https://ask.fm" . $itemAge->attr('href');
            $date = $itemAge->attr('title');

            $feed->add($question, $user, $link, $date, $answer, $answer);
        });

        return $feed->render('atom');
    }
}
