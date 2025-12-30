<?php

namespace App\Filament\Resources\RepairItemResource\Pages;

use App\Filament\Resources\RepairItemResource;
use App\Models\RepairItem; // 引入 Model
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListRepairItems extends ListRecords
{
    protected static string $resource = RepairItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            // === 批量新增按鈕 ===
            Actions\Action::make('batchCreate')
                ->label('批量新增項目')
                ->icon('heroicon-o-queue-list')
                ->color('success')
                ->form([
                    Forms\Components\Textarea::make('names')
                        ->label('項目名稱列表 (一行一個)')
                        ->placeholder("更換電池\n更換螢幕\n主機板維修")
                        ->rows(10)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $names = explode("\n", $data['names']);
                    $count = 0;

                    // 取得目前最大的排序值，以便往後疊加
                    $currentMaxSort = RepairItem::max('sort_order') ?? 0;

                    foreach ($names as $name) {
                        $name = trim($name);
                        if (empty($name)) continue;

                        $currentMaxSort++;

                        RepairItem::firstOrCreate(
                            ['name' => $name],
                            ['sort_order' => $currentMaxSort] // 自動給予排序
                        );
                        $count++;
                    }

                    Notification::make()
                        ->title("成功建立 {$count} 個維修項目")
                        ->success()
                        ->send();
                }),
        ];
    }
}

?>
