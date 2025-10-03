<?php

namespace App\Filament\Resources\SellerSubscriptionResource\Pages;

use App\Filament\Resources\SellerSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSellerSubscription extends EditRecord
{
    protected static string $resource = SellerSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
