<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IzurviveLocation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'izurvive_locations';

    /**
     * Mass-assignable attributes.
     *
     * @var array
     */
    protected $fillable = ['name_en', 'name_ru', 'latitude', 'longitude'];

    /**
     * Get the various spellings for a location.
     */
    public function spellings()
    {
        return $this->hasMany('App\IzurviveLocationSpelling', 'location_id');
    }
}
