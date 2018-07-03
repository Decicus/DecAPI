<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RateLimitApiKeys extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'rate_limit_api_keys';

    /**
     * The column to set as the primary key.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'api_key',
        'enabled',
    ];

    /**
     * The attributes that should be visible in arrays.
     *
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'description',
        'api_key',
        'enabled',
    ];
}
