<?php

namespace App\Http\Resources\Twitch;

use Illuminate\Http\Resources\Json\JsonResource;

class Channel extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $res = $this->resource;

        return [
            'id' =>  $res['broadcaster_id'],
            'name' => $res['broadcaster_name'],
            'language' => $res['broadcaster_language'],
            'game' => [
                'id' => $res['game_id'],
                'name' => $res['game_name'],
            ],
            'title' => $res['title'],
        ];
    }
}
