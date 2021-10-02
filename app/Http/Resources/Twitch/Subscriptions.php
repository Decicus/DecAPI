<?php

namespace App\Http\Resources\Twitch;

use Illuminate\Http\Resources\Json\JsonResource;

class Subscriptions extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $resource = $this->resource;
        $subscriptions = Subscription::collection(collect($resource['data']));
        $pagination = Pagination::make($resource['pagination']);

        return [
            'subscriptions' => $subscriptions,
            'points' => $resource['points'],
            'count' => $resource['total'],
            'pagination' => $pagination,
        ];
    }
}
