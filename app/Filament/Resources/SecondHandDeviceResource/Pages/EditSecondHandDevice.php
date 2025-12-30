<?php

namespace App\Filament\Resources\SecondHandDeviceResource\Pages;

use App\Filament\Resources\SecondHandDeviceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSecondHandDevice extends EditRecord
{
    protected static string $resource = SecondHandDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
