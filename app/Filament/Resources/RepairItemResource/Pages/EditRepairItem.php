<?php

namespace App\Filament\Resources\RepairItemResource\Pages;

use App\Filament\Resources\RepairItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRepairItem extends EditRecord
{
    protected static string $resource = RepairItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
