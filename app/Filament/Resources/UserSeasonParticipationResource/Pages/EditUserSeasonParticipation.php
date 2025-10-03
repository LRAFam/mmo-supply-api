<?php

namespace App\Filament\Resources\UserSeasonParticipationResource\Pages;

use App\Filament\Resources\UserSeasonParticipationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserSeasonParticipation extends EditRecord
{
    protected static string $resource = UserSeasonParticipationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
