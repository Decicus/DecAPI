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
         * This will likely change in the future, so we just default to 3600 seconds (1 hour) whenever it _isn't_ included.
         */
        $expiresIn = $this->resource['expires_in'] ?? 3600;
        $expires = now()->addSeconds($expiresIn);

        return [
            'access_token' => $this->resource['access_token'],
            'refresh_token' => $this->resource['refresh_token'],
            'expires' => $expires,
            'scope' => $this->resource['scope'],
            'type' => $this->resource['token_type'],
        ];
    }
}
