<?php

namespace App\Http\Resources\TwitchEmotes;

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

        return [
            'code' => $res['code'],
            'set' => $res['emoticon_set'],
            'id' => $res['id'],
        ];
    }
}
