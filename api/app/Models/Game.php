<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'logo',
        'icon',
        'description',
        'provider_count',
    ];

    protected $casts = [
        'provider_count' => 'integer',
    ];
}
