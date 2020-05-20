<?php

namespace App\Http\Resources\TwitchEmotes;

use Illuminate\Http\Resources\Json\ResourceCollection;

class Plans extends ResourceCollection
{
    // Should convert the resource into an array
    public function toArray($request)
    {
        $res = $this->resource;

        return [
            'tier1' => $res['$4.99'],
            'tier2' => $res['$9.99'],
            'tier3' => $res['$24.99'],
        ];
    }

    /**
     * Sorts emotes by their tier, returns them as an array
     *
     * @param array $emotes
     * @return array
     */
    public function sortEmotes($emotes)
    {
        return $this->collection
                    ->map(function($plans) use($emotes){
                        $accumulator = [];
                        foreach ($emotes as $emote){
                            if ($emote['emoticon_set'] == $plans){
                                array_push($accumulator, $emote['code']);
                            };
                        }
                        return($accumulator);
                    })
                    ->toArray();
    }
}
