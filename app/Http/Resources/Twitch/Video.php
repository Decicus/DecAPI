<?php

namespace App\Http\Resources\Twitch;

use Illuminate\Http\Resources\Json\JsonResource;

class Video extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $video = $this->resource;

        return [
            'id' => $video['id'],
            'stream_id' => $video['stream_id'],
            'user_id' => $video['user_id'],
            'user_login' => $video['user_login'],
            'user_name' => $video['user_name'],
            'title' => $video['title'],
            'description' => $video['description'],
            'created_at' => $video['created_at'],
            'published_at' => $video['published_at'],
            'url' => $video['url'],
            'thumbnail_url' => $video['thumbnail_url'],
            'viewable' => $video['viewable'],
            'view_count' => $video['view_count'],
            'language' => $video['language'],
            'type' => $video['type'],
            'duration' => $video['duration'],
            'muted_segments' => $video['muted_segments'] ?? [],
        ];
    }
}
