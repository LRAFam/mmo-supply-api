<?php

namespace App\Filament\Resources\FeaturedListingResource\Pages;

use App\Filament\Resources\FeaturedListingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFeaturedListing extends CreateRecord
{
    protected static string $resource = FeaturedListingResource::class;
}
