<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TwitchUserToken extends JsonResource
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
            'refresh_token' => $this->resource['refresh_token'],
            'expires' => $expires,
            'scope' => $this->resource['scope'],
            'type' => $this->resource['token_type'],
        ];
    }
}
