<?php

namespace App\Http\Resources\Twitch;
use Illuminate\Http\Resources\Json\JsonResource;

class ChannelFollowers extends JsonResource
{
    public function toArray($request)
    {
        $res = $this->resource;

        return [
            'followers' => $res['data'] ?? [],
            'total' => $res['total'],
        ];
    }
}
