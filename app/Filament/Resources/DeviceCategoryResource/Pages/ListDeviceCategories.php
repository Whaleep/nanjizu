<?php

namespace App\Filament\Resources\DeviceCategoryResource\Pages;

use App\Filament\Resources\DeviceCategoryResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListDeviceCategories extends ListRecords
{
    protected static string $resource = DeviceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('batchCreate')
                ->label('批量新增系列')
                ->form([
                    Forms\Components\Select::make('brand_id')
                        ->relationship('brand', 'name')
                        ->label('所屬品牌')
                        ->required(),
                    Forms\Components\Textarea::make('names')
                        ->label('系列名稱列表 (一行一個)')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $names = explode("\n", $data['names']);
                    foreach ($names as $name) {
                        $name = trim($name);
                        if (empty($name)) continue;
                        \App\Models\DeviceCategory::firstOrCreate([
                            'brand_id' => $data['brand_id'],
                            'name' => $name
                        ]);
                    }
                    Notification::make()->title('系列建立完成')->success()->send();
                }),
        ];
    }
}
