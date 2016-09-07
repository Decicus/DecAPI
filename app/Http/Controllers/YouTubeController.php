<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use YouTube;
use App\Helpers\Helper;

class YouTubeController extends Controller
{
    /**
     * Retrieves the latest public YouTube upload from the specified identifier.
     *
     * @param  Request $request
     * @return Response
     */
    public function latestVideo(Request $request)
    {
        $type = null;
        if ($request->has('user')) {
            $type = 'user';
        }

        if ($request->has('id')) {
            $type = 'id';
        }

        if (empty($type)) {
            return Helper::text('You need to specify a "user" (/user/ URLs) or an "id" (/channel/ URLs).');
        }

        $id = $request->input($type, null);
        $skip = intval($request->input('skip', 0));

        if ($skip >= 50) {
            $skip = 0;
        }

        switch ($type) {
            case 'user':
                $channel = YouTube::getChannelByName($id);
                break;

            default:
                $channel = YouTube::getChannelById($id);
                break;
        }

        if ($channel === false) {
            return Helper::text('The specified identifier is invalid.');
        }

        $playlistId = $channel->contentDetails->relatedPlaylists->uploads;

        $uploads = YouTube::getPlaylistItemsByPlaylistId($playlistId);

        if (empty($uploads['results'])) {
            return Helper::text('This channel has no public videos.');
        }

        $results = $uploads['results'];
        $total = count($uploads['results']);

        // Check if the channel has even uploaded the amount of videos the user wants to skip.
        if ($total < ($skip + 1)) {
            return Helper::text('Invalid skip count specified for this channel.');
        }

        $video = $uploads['results'][$skip];
        return Helper::text($video->snippet->title . ' - https://youtu.be/' . $video->contentDetails->videoId);
    }
}
