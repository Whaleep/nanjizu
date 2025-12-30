<?php

namespace App\Filament\Resources\ShopMenuResource\Pages;

use App\Filament\Resources\ShopMenuResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShopMenu extends EditRecord
{
    protected static string $resource = ShopMenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
