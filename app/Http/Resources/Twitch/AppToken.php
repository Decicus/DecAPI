<?php

namespace App\Http\Resources\Twitch;

use Illuminate\Http\Resources\Json\JsonResource;

class AppToken extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $expires = now()->addSeconds($this->resource['expires_in']);

        return [
            'access_token' => $this->resource['access_token'],
            'expires' => $expires,
            'type' => $this->resource['token_type'],
        ];
    }
}
