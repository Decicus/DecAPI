<?php

namespace App\Http\Resources\TwitchEmotes;

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

        $emotes = EmotesCollection::make(collect($res['emotes']));

        return [
            'name' => $res['channel_name'],
            'display_name' => $res['display_name'],
            'id' => $res['channel_id'],
            'broadcaster_type' => $res['broadcaster_type'],
            'base_set_id' => $res['base_set_id'],
            'generated_at' => $res['generated_at'],

            'emotes' => $emotes,
            'plans' => $res['plans'],
            'badges' => [
                'subscribers' => $res['subscriber_badges'] ?? null,
                'bits' => $res['bits_badges'] ?? null,
            ],
            'cheermotes' => $res['cheermotes'] ?? null,
        ];
    }
}
