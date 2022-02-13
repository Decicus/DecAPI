<?php

namespace App\Http\Resources\Twitch;

use Illuminate\Http\Resources\Json\JsonResource;

class Team extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $team = $this->resource;
        $users = TeamUser::collection(collect($team['users']));

        return [
            'id' => $team['id'],
            'background_image' => $team['background_image_url'],
            'banner' => $team['banner'],
            'created_at' => $team['created_at'],
            'updated_at' => $team['updated_at'],
            'info' => $team['info'],
            'thumbnail' => $team['thumbnail_url'],
            'name' => $team['team_name'],
            'display_name' => $team['team_display_name'],
            'users' => $users,
        ];
    }
}
