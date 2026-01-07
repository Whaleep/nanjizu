<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),

            \Filament\Actions\Action::make('print')
                ->label('列印出貨單')
                ->icon('heroicon-o-printer')
                ->url(route('admin.orders.print', $this->record))
                ->openUrlInNewTab(),
        ];
    }
}
