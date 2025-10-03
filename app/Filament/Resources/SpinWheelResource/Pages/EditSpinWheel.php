<?php

namespace App\Filament\Resources\SpinWheelResource\Pages;

use App\Filament\Resources\SpinWheelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSpinWheel extends EditRecord
{
    protected static string $resource = SpinWheelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
