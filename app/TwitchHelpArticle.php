<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TwitchHelpArticle extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'twitch_help_articles';

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
     * Scope a query to search.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param  String $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('title', 'LIKE', '%' . strtolower($search) . '%');
    }

    /**
     * The associated TwitchHelpCategory model.
     *
     * @return App\TwitchHelpCategory
     */
    public function category()
    {
        return $this->belongsTo('App\TwitchHelpCategory', 'id', 'category_id');
    }
}
