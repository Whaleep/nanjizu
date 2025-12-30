<?php

namespace App\Filament\Resources\BrandResource\Pages;

use App\Filament\Resources\BrandResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListBrands extends ListRecords
{
    protected static string $resource = BrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('batchCreate')
                ->label('批量新增品牌')
                ->form([
                    Forms\Components\Textarea::make('names')
                        ->label('品牌名稱列表 (一行一個)')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $names = explode("\n", $data['names']);
                    foreach ($names as $name) {
                        $name = trim($name);
                        if (empty($name)) continue;
                        \App\Models\Brand::firstOrCreate(['name' => $name]);
                    }
                    Notification::make()->title('品牌建立完成')->success()->send();
                }),
        ];
    }
}
