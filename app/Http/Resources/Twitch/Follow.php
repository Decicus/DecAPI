<?php

namespace App\Http\Resources\Twitch;

use Illuminate\Http\Resources\Json\JsonResource;

class Follow extends JsonResource
{
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $follows = FollowUser::collection(collect($this->resource['data']));
        $pagination = Pagination::make($this->resource['pagination']);

        return [
            'total' => $this->resource['total'],
            'follows' => $follows->resolve(),
            'pagination' => $pagination->resolve(),
        ];
    }
}
