<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use PHPHtmlParser;
use Feed;
use Carbon\Carbon;

class AskfmController extends Controller
{
    public function rss(Request $request, $user = null)
    {
        $user = $user ?: $request->input('user', null);
        $feed = new Feed;
        $feed->setView('vendor.feed.atom');

        if (empty($user)) {
            $feed->title = 'Ask.fm - RSS Feed';
            $feed->description = 'RSS feed covering answers of users on Ask.fm';
            $date = new Carbon();
            $msg = 'User has to be specified';
            $feed->add($msg, $msg, url($request->fullUrl()), $date->toAtomString(), $msg, $msg);
            return $feed->render('atom');
        }

        $dom = new PHPHtmlParser\Dom;
        $dom->loadFromUrl('https://ask.fm/' . $user);
        $answers = $dom->find('.item-pager')->find('.streamItem-answer');

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

        $ageStr = '.streamItemsAge a';
        $date = $answers[0]->find($ageStr)->getAttribute('title');
        $feed->pubdate = $date;

        foreach ($answers as $a) {
            $itemAge = $a->find($ageStr);

            $question = trim($a->find('.streamItemContent-question')->firstChild()->text);
            $answer = trim($a->find('.streamItemContent-answer')->firstChild()->text);
            $link = "https://ask.fm" . $itemAge->getAttribute('href');
            $date = $itemAge->getAttribute('title');

            $feed->add($question, $user, $link, $date, $answer, $answer);
        }
        return $feed->render('atom');
    }
}
