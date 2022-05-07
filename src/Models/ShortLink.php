<?php

namespace Chowjiawei\ShortLink\Models;

use Illuminate\Database\Eloquent\Model;

class ShortLink extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable=['redirect_old','redirect_new'];

    const CREATED_AT = 'created_at_gmt';
    const UPDATED_AT = 'updated_at_gmt';

    protected $visible = [
        'redirect_old',
        'redirect_new',
        'id',
        'created_at_gmt',
        'updated_at_gmt',
    ];
}
