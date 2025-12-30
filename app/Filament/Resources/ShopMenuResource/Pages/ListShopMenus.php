<?php

namespace App\Filament\Resources\ShopMenuResource\Pages;

use App\Filament\Resources\ShopMenuResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShopMenus extends ListRecords
{
    protected static string $resource = ShopMenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
