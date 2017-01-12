<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Helpers\Helper;
use App\Http\Requests;
use PHPHtmlParser;

class Rainbow6Controller extends Controller
{
    /**
     * Retrieves the latest forum posts in "News & announcements"
     * on the "Rainbow Six: Siege" Ubisoft forums, then filters through
     * looking for "Patch Notes" in the title.
     *
     * @return Response
     */
    public function patchNotes(Request $request)
    {
        $baseUrl = 'http://forums.ubi.com/';
        $forum = $baseUrl . 'forumdisplay.php/1074-News-amp-Announcements';

        $offset = intval($request->input('offset', 0));

        $dom = new PHPHtmlParser\Dom;
        $dom->loadFromUrl($forum);
        $threads = $dom->find('ol#threads')->find('li');

        $i = 0;
        foreach ($threads as $item) {
            $class = $item->getAttribute('class');
            if (strpos($class, 'threadbit') === false) {
                continue;
            }

            $thread = $item->find('.threadtitle');

            // Deleted/closed threads
            if (trim($thread->text) !== '') {
                continue;
            }

            $thread = $thread->find('a');

            $title = $thread->text;

            // Not patch notes = not interested
            if (strpos(strtolower($title), 'patch notes') === false) {
                continue;
            }

            // `offset` query parameter
            if ($offset > $i) {
                $i++;
                continue;
            }

            $format = sprintf('%s: %s%s', $title, $baseUrl, $thread->getAttribute('href'));
            return Helper::text($format);
        }

        return Helper::text('Unable to find the latest patch notes in the Ubisoft forums.');
    }
}
