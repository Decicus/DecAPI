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
        $expiresIn = $this->resource['expires_in'] ?? 604800;
        $expires = now()->addSeconds($expiresIn);

        return [
            'access_token' => $this->resource['access_token'],
            'expires' => $expires,
            'type' => $this->resource['token_type'],
        ];
    }
}
