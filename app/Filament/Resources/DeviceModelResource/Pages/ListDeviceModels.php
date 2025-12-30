<?php

namespace App\Filament\Resources\DeviceModelResource\Pages;

use App\Filament\Resources\DeviceModelResource;
use App\Models\DeviceCategory;
use App\Models\DeviceModel;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Str;

class ListDeviceModels extends ListRecords
{
    protected static string $resource = DeviceModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 原本的新增按鈕
            Actions\CreateAction::make(),

            // === 新增：批量快速建立按鈕 ===
            Actions\Action::make('batchCreate')
                ->label('批量新增型號')
                ->icon('heroicon-o-queue-list') // 列表圖示
                ->color('success') // 綠色按鈕
                ->form([
                    // 1. 選擇品牌
                    Forms\Components\Select::make('brand_id')
                        ->relationship('brand', 'name')
                        ->label('所屬品牌')
                        ->required()
                        ->live(), // 開啟連動

                    // 2. 選擇系列 (根據品牌篩選)
                    Forms\Components\Select::make('device_category_id')
                        ->label('產品系列')
                        ->options(function (Forms\Get $get) {
                            $brandId = $get('brand_id');
                            if (!$brandId) return [];
                            return DeviceCategory::where('brand_id', $brandId)->pluck('name', 'id');
                        })
                        ->required(),

                    // 3. 貼上名稱的大框框
                    Forms\Components\Textarea::make('names')
                        ->label('型號列表 (請從 Excel 複製貼上，一行一個)')
                        ->helperText('系統會自動過濾空白行，並建立網址代稱。')
                        ->rows(10) // 高度設高一點方便貼上
                        ->placeholder("iPhone 15\niPhone 15 Pro\niPhone 15 Pro Max")
                        ->required(),
                ])
                ->action(function (array $data): void {
                    // 取得使用者輸入的文字，依據換行符號切割成陣列
                    $names = explode("\n", $data['names']);

                    $count = 0;

                    foreach ($names as $name) {
                        $name = trim($name); // 去除前後空白

                        // 如果是空行就跳過
                        if (empty($name)) continue;

                        // 建立資料 (使用 firstOrCreate 防止重複建立同名型號)
                        DeviceModel::firstOrCreate(
                            [
                                'brand_id' => $data['brand_id'],
                                'device_category_id' => $data['device_category_id'],
                                'name' => $name,
                            ],
                            [
                                // 如果是新建立的，自動產生 slug
                                'slug' => Str::slug($name),
                                'sort_order' => 0,
                            ]
                        );

                        $count++;
                    }

                    // 發送成功通知
                    Notification::make()
                        ->title("成功建立 {$count} 筆型號")
                        ->success()
                        ->send();
                }),
        ];
    }
}
