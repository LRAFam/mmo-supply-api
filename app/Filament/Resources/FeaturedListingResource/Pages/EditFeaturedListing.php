<?php

namespace App\Filament\Resources\FeaturedListingResource\Pages;

use App\Filament\Resources\FeaturedListingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFeaturedListing extends EditRecord
{
    protected static string $resource = FeaturedListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
