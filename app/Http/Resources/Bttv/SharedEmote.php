<?php

namespace App\Http\Resources\Bttv;

use Illuminate\Http\Resources\Json\JsonResource;

class SharedEmote extends JsonResource
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

        /**
         * There hasn't been an occurrence where this is omitted,
         * but I have a feeling some legacy options might have that.
         */
        $user = $res['user'] ?? [
            'id' => null,
            'name' => null,
            'displayName' => null,
            'providerId' => null,
        ];

        return [
            'id' => $res['id'],
            'code' => $res['code'],
            'type' => $res['imageType'],

            /**
             * In the `/cached/users/twitch/{id}` endpoint these are omitted.
             */
            'created_at' => $res['createdAt'] ?? null,
            'updated_at' => $res['updatedAt'] ?? null,
            'global' => $res['global'] ?? null,
            'live' => $res['live'] ?? null,
            'sharing' => $res['sharing'] ?? null,
            'approval_status' => $res['approvalStatus'] ?? null,

            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'display_name' => $user['displayName'],
                'provider_id' => $user['providerId'],
            ],
        ];
    }
}
