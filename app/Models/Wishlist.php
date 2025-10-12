<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wishlistable_type',
        'wishlistable_id',
    ];

    /**
     * Get the user that owns the wishlist item
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the wishlistable model (Item, Currency, Account, or Service)
     */
    public function wishlistable()
    {
        return $this->morphTo();
    }
}
