<?php

namespace App\Filament\Resources\PayPalPayoutResource\Pages;

use App\Filament\Resources\PayPalPayoutResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePayPalPayout extends CreateRecord
{
    protected static string $resource = PayPalPayoutResource::class;
}
