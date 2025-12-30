<?php

namespace App\Filament\Resources\RepairCaseResource\Pages;

use App\Filament\Resources\RepairCaseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRepairCase extends EditRecord
{
    protected static string $resource = RepairCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
