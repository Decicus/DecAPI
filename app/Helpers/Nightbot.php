<?php 

namespace App\Helpers;

use Illuminate\Http\Request;

class Nightbot {
    /**
     * Holds the "Nightbot-Channel" header data
     * 
     * @var array|null
     */
    public $channel = null;
    
    /**
     * Holds the "Nightbot-User" header data
     * 
     * @var array|null
     */
    public $user = null;
    
    /**
     * Initializes the helper.
     * 
     * @param Request $request The request that includes the Nightbot headers.
     */
    public function __construct(Request $request)
    {
        $channel = $request->header('Nightbot-Channel');
        $user = $request->header('Nightbot-User');
        
        if (!empty($channel)) {
            parse_str($channel, $this->channel);
        }
        
        if (!empty($user)) {
            parse_str($user, $this->user);
        }
    }
}