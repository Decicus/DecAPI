<?php

// aaaaaaaaaaaaaaaAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA

namespace App\Http\Resources\TwitchEmotes;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Log;

class Plans extends ResourceCollection
{
    // AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
    public function toArray($request)
    {
        $res = $this->resource;

        return [
            'tier1' => $res['$4.99'],
            'tier2' => $res['$9.99'],
            'tier3' => $res['$24.99'],
        ];
    }

    // sorts emotes by plan name,  e.g. all emotes from 4.99 set would be together in one key ("$4.99")
    // so yeah.. I know this could be done better
    // for example, it could return tier1, tier2, tier3 instead of dollar values
    // ^ EDIT: covered in TwitchController though it still feels wrong ^
    // how do i do that documentation thing?
    public function sortEmotes($emotes)
    {
        return $this->collection
                    ->map(function($plans, $AAAAAa) use($emotes){
                        $accumulator = array();
                        foreach($emotes as $emote){
                            if($emote['emoticon_set'] == $plans){
                                array_push($accumulator, $emote['code']);
                            };
                        }
                        return($accumulator);
                    })
                    ->toArray();
    }
}
