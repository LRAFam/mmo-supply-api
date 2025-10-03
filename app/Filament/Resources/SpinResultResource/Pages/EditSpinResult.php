<?php

namespace App\Filament\Resources\SpinResultResource\Pages;

use App\Filament\Resources\SpinResultResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSpinResult extends EditRecord
{
    protected static string $resource = SpinResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
