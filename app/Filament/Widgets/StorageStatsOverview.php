<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\File;

class StorageStatsOverview extends BaseWidget
{
    // 設定重新整理頻率 (例如每 60 秒)
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        // 計算 storage/app/public 目錄大小
        $path = storage_path('app/public');
        $size = 0;

        if (File::exists($path)) {
            foreach (File::allFiles($path) as $file) {
                $size += $file->getSize();
            }
        }

        // 轉換為 MB
        $sizeInMb = round($size / 1024 / 1024, 2);

        // 假設主機限額 10GB (可自訂)
        $limit = 10 * 1024;
        $percentage = round(($sizeInMb / $limit) * 100, 1);

        return [
            Stat::make('圖片佔用空間', $sizeInMb . ' MB')
                ->description("已使用 {$percentage}% (總量 10GB)")
                ->descriptionIcon('heroicon-m-photo')
                ->color($percentage > 80 ? 'danger' : 'success')
                ->chart([$sizeInMb, $sizeInMb]), // 簡單顯示
        ];
    }
}
