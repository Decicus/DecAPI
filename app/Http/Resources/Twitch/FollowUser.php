<?php

namespace App\Http\Resources\Twitch;

use Illuminate\Http\Resources\Json\JsonResource;

class FollowUser extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // https://dev.twitch.tv/docs/api/reference/#get-users-follows
        return [
            'from_id' => $this->resource['from_id'],
            'from_name' => $this->resource['from_name'],
            'to_id' => $this->resource['to_id'],
            'to_name' => $this->resource['to_name'],
            'followed_at' => $this->resource['followed_at'],
        ];
    }
}
