<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Order;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class OrdersChart extends ChartWidget
{
    protected static ?string $heading = '近 30 天訂單趨勢';
    protected static ?int $sort = 2; // 排在 Stats 後面

    protected function getData(): array
    {
        // 統計每天的訂單數量
        $data = Trend::model(Order::class)
            ->between(
                start: now()->subDays(30),
                end: now(),
            )
            ->perDay()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => '訂單數量',
                    'data' => $data->map(fn(TrendValue $value) => $value->aggregate),
                    'borderColor' => '#3b82f6', // 藍色
                    'fill' => 'start',
                ],
            ],
            'labels' => $data->map(fn(TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line'; // 折線圖
    }
}
