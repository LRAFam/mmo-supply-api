<?php

namespace App\Filament\Resources\PayPalPayoutResource\Pages;

use App\Filament\Resources\PayPalPayoutResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPayPalPayouts extends ListRecords
{
    protected static string $resource = PayPalPayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
