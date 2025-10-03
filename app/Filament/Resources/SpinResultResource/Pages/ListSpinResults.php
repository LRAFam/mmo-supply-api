<?php

namespace App\Filament\Resources\SpinResultResource\Pages;

use App\Filament\Resources\SpinResultResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSpinResults extends ListRecords
{
    protected static string $resource = SpinResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
