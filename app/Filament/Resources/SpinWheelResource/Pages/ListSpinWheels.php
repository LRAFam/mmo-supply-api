<?php

namespace App\Filament\Resources\SpinWheelResource\Pages;

use App\Filament\Resources\SpinWheelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSpinWheels extends ListRecords
{
    protected static string $resource = SpinWheelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
