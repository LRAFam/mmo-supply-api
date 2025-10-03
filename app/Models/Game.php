<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Game extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'logo',
        'icon',
        'description',
        'provider_count',
        'is_featured',
        'is_active',
    ];

    protected $casts = [
        'provider_count' => 'integer',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = ['logo_url', 'icon_url'];

    /**
     * Get the full URL for the logo
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->logo ? Storage::disk('s3')->url($this->logo) : null,
        );
    }

    /**
     * Get the full URL for the icon
     */
    protected function iconUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->icon ? Storage::disk('s3')->url($this->icon) : null,
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function currencies(): HasMany
    {
        return $this->hasMany(Currency::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function providers(): HasMany
    {
        return $this->hasMany(Provider::class);
    }
}
