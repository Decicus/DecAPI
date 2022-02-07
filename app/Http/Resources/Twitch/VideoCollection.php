<?php

namespace App\Http\Resources\Twitch;

use Illuminate\Http\Resources\Json\ResourceCollection;

class VideoCollection extends ResourceCollection
{
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
