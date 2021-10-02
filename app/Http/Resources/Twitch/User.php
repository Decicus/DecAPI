<?php

namespace App\Http\Resources\Twitch;

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
        $user = $this->resource;

        return [
            'id' => $user['id'],
            'login' => $user['login'],
            'display_name' => $user['display_name'],
            'type' => $user['type'],
            'broadcaster_type' => $user['broadcaster_type'],
            'description' => $user['description'],
            'avatar' => $user['profile_image_url'],
            'offline_image' => $user['offline_image_url'],
            'view_count' => $user['view_count'],
            'email' => $user['email'] ?? null,
            'created_at' => $user['created_at'],
        ];
    }
}
