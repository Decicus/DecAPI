<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TwitchHelpCategory extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'twitch_help_categories';

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
        'id'
    ];

    /**
     * The associated TwitchHelpArticle models.
     *
     * @return App\TwitchHelpArticle
     */
    public function articles()
    {
        return $this->hasMany('App\TwitchHelpArticle', 'category_id', 'id');
    }
}
