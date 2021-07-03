<?php

namespace App\Http\Resources\Twitch;

use Illuminate\Http\Resources\Json\JsonResource;

class Emote extends JsonResource
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
        $images = $res['images'];

        return [
            'code' => $res['name'],
            'images' => [
                'small' => $images['url_1x'] ?? null,
                'medium' => $images['url_2x'] ?? null,
                'large' => $images['url_4x'] ?? null,
            ],
            'tier' => $res['tier'],
            'type' => $res['emote_type'],
            'set' => $res['emote_set_id'],
            'id' => $res['id'],
        ];
    }
}
