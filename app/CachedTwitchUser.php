<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CachedTwitchUser extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cached_twitch_users';

    /**
     * The column to set as the primary key.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if IDs are auto-incrementing.
     *
     * @var boolean
     */
    public $incrementing = false;

    /**
     * The attributes that are mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'username'
    ];

    /**
     * The associated User model.
     *
     * @return App\User
     */
    public function user()
    {
        return $this->hasOne('App\User', 'id', 'id');
    }
}
