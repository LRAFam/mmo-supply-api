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
        \Log::info('Game save - BEFORE:', [
            'data' => $data,
            'logo_exists' => isset($data['logo']) && $data['logo'] ? Storage::disk('s3')->exists($data['logo']) : 'N/A',
            'icon_exists' => isset($data['icon']) && $data['icon'] ? Storage::disk('s3')->exists($data['icon']) : 'N/A',
            'all_s3_files' => Storage::disk('s3')->allFiles(),
        ]);

        if (isset($data['logo']) && $data['logo'] !== $record->logo && $record->logo) {
            Storage::disk('s3')->delete($record->logo);
        }

        if (isset($data['icon']) && $data['icon'] !== $record->icon && $record->icon) {
            Storage::disk('s3')->delete($record->icon);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        \Log::info('Game save - AFTER:', [
            'logo' => $record->logo,
            'icon' => $record->icon,
            'logo_exists' => $record->logo ? Storage::disk('s3')->exists($record->logo) : 'N/A',
            'icon_exists' => $record->icon ? Storage::disk('s3')->exists($record->icon) : 'N/A',
            'all_s3_files' => Storage::disk('s3')->allFiles(),
        ]);
    }
}
