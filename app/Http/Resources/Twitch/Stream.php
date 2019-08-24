<?php

namespace App\Http\Resources\Twitch;

use Illuminate\Http\Resources\Json\JsonResource;

class Stream extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // https://dev.twitch.tv/docs/api/reference/#get-streams
        $stream = $this->resource;
        return [
            'id' => $stream['id'],
            'user' => [
                'id' => $stream['user_id'],
                'name' => $stream['user_name'],
            ],
            'game' => $stream['game_id'],
            'type' => $stream['type'],
            'title' => $stream['title'],
            'viewers' => $stream['viewer_count'],
            'created_at' => $stream['started_at'],
            'thumbnail' => $stream['thumbnail_url'],
            'tags' => $stream['tag_ids'],
        ];
    }
}
