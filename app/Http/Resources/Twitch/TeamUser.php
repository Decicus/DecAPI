<?php

namespace App\Http\Resources\Twitch;

use Illuminate\Http\Resources\Json\JsonResource;

class TeamUser extends JsonResource
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
            'user_id' => $user['user_id'],
            'user_name' => $user['user_name'],
            'user_login' => $user['user_login'],
        ];
    }
}
