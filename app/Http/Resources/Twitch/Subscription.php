<?php

namespace App\Http\Resources\Twitch;

use Illuminate\Http\Resources\Json\JsonResource;

class Subscription extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $subscription = $this->resource;

        return [
            'broadcaster_id' => $subscription['broadcaster_id'],
            'broadcaster_name' => $subscription['broadcaster_name'],
            'gifter_id' => $subscription['gifter_id'],
            'gifter_name' => $subscription['gifter_name'],
            'gifter_login' => $subscription['gifter_login'],
            'gift' => $subscription['is_gift'],
            'plan' => $subscription['plan_name'],
            'tier' => $subscription['tier'],
            'user_id' => $subscription['user_id'],
            'user_name' => $subscription['user_name'],
            'user_login' => $subscription['user_login'],
        ];
    }
}
