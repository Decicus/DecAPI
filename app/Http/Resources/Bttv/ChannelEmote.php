<?php

namespace App\Http\Resources\Bttv;

use Illuminate\Http\Resources\Json\JsonResource;

class ChannelEmote extends JsonResource
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

        $restrictions = $res['restrictions'] ?? [
            'games' => [],
            'channels' => [],
            'emoticonSet' => null,
        ];

        return [
            'id' => $res['id'],
            'code' => $res['code'],
            'type' => $res['imageType'],
            'user_id' => $res['userId'],

            /**
             * Only seen this with night's "special" emotes.
             */
            'restrictions' => $restrictions,

            /**
             * In the `/cached/users/twitch/{id}` endpoint these are omitted.
             */
            'created_at' => $res['createdAt'] ?? null,
            'updated_at' => $res['updatedAt'] ?? null,
            'global' => $res['global'] ?? null,
            'live' => $res['live'] ?? null,
            'sharing' => $res['sharing'] ?? null,
            'approval_status' => $res['approvalStatus'] ?? null,
        ];
    }
}
