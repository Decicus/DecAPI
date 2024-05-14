<?php

namespace App\Http\Resources\Twitch;

use Illuminate\Http\Resources\Json\JsonResource;

class AuthToken extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /**
         * According to Twitch API documentation, this is supposed to be included in the response.
         * Turns out that if you're using "legacy" Twitch API client credentials, then expires_in is just excluded (for now).
         *
         * In 2019 I set the default expires to one hour.
         * In 2024 I realized an hour is far too little and that said likely change still hasn't occurred.
         * Bumped up the default expiry to 1 week (604800 seconds).
         */
        $expiresIn = $this->resource['expires_in'] ?? 604800;
        $expires = now()->addSeconds($expiresIn);

        return [
            'access_token' => $this->resource['access_token'],
            'refresh_token' => $this->resource['refresh_token'] ?? null,
            'expires' => $expires,
            'scope' => $this->resource['scope'] ?? null,
            'type' => $this->resource['token_type'],
        ];
    }
}
