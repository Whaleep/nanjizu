<?php

namespace App\Filament\Resources\SecondHandDeviceResource\Pages;

use App\Filament\Resources\SecondHandDeviceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSecondHandDevices extends ListRecords
{
    protected static string $resource = SecondHandDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
