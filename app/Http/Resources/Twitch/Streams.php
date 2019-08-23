<?php

namespace App\Http\Resources\Twitch;

use Illuminate\Http\Resources\Json\JsonResource;

class Streams extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $streams = Stream::collection(collect($this->resource['data']));
        $pagination = Pagination::make($this->resource['pagination']);

        return [
            'streams' => $streams->resolve(),
            'pagination' => $pagination->resolve(),
        ];
    }
}
