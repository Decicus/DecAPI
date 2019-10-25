<?php

namespace App\Http\Resources\Bttv;

use Illuminate\Http\Resources\Json\JsonResource;

class User extends JsonResource
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
        $channelEmotes = ChannelEmote::collection(collect($res['channelEmotes']));
        $sharedEmotes = SharedEmote::collection(collect($res['sharedEmotes']));

        return [
            'id' => $res['id'],
            'name' => $res['name'],
            'display_name' => $res['displayName'],
            'provider_id' => $res['providerId'],

            /**
             * Array of Twitch usernames
             */
            'bots' => $res['bots'],

            'emotes' => [
                'channel' => $channelEmotes->resolve(),
                'shared' => $sharedEmotes->resolve(),
            ],
        ];
    }
}
