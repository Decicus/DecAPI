<?php

namespace App\Http\Resources\Twitch;

use Illuminate\Http\Resources\Json\ResourceCollection;

class EmoteCollection extends ResourceCollection
{
    /**
     * Return the emote codes
     *
     * @return array
     */
    public function codes()
    {
        return $this->collection
                    ->map(function($emote) {
                        return $emote['code'];
                    })
                    ->toArray();
    }

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection
                    ->map
                    ->toArray($request)
                    ->all();
    }
}
