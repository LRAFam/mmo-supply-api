<?php

namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Resources\GameResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditGame extends EditRecord
{
    protected static string $resource = GameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // When updating logo/icon, delete old files from S3
        $record = $this->getRecord();

        // Debug logging
        \Log::info('Game save data:', $data);

        if (isset($data['logo']) && $data['logo'] !== $record->logo && $record->logo) {
            Storage::disk('s3')->delete($record->logo);
        }

        if (isset($data['icon']) && $data['icon'] !== $record->icon && $record->icon) {
            Storage::disk('s3')->delete($record->icon);
        }

        return $data;
    }
}
