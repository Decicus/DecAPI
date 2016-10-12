<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Vinelab\Rss\Rss;
use App\Helpers\Helper;

class Rainbow6Controller extends Controller
{
    /**
     * Retrieves the latest forum posts in "News & announcements"
     * on the "Rainbow Six: Siege" Ubisoft forums, then filters through
     * looking for "Patch Notes" in the title.
     *
     * @return Response
     */
    public function patchNotes()
    {
        $feedUrl = 'http://forums.ubi.com/external.php?type=rss2&forumids=1074';
        $rss = new Rss;
        $feed = $rss->feed($feedUrl);
        $articles = $feed->articles();

        foreach ($articles as $article) {
            if (strpos(strtolower($article->title), 'patch notes') !== false) {
                return Helper::text(sprintf('%s - %s', $article->title, $article->guid));
            }
        }

        return Helper::text('No results found.');
    }
}
