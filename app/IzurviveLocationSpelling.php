<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IzurviveLocationSpelling extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'izurvive_location_spellings';

    /**
     * Mass-assignable attributes.
     *
     * @var array
     */
    protected $fillable = ['location_id', 'spelling'];

    /**
     * Returns the location the spelling is associated to.
     */
    public function location()
    {
        return $this->belongsTo('App\IzurviveLocation', 'location_id', 'id');
    }
}
