<?php

namespace App\Filament\Resources\RepairCaseResource\Pages;

use App\Filament\Resources\RepairCaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRepairCases extends ListRecords
{
    protected static string $resource = RepairCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
