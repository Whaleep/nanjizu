<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\DeviceModel;
use App\Models\Inquiry;

class RepairService
{
    /**
     * 取得所有品牌，並預先載入分類與排序好的型號
     */
    public function getBrandsWithHierarchy()
    {
        return Brand::with([
            'deviceCategories',
            'deviceModels' => function ($query) {
                $query->orderBy('sort_order', 'asc');
            }
        ])
        ->orderBy('sort_order', 'asc')
        ->get();
    }

    /**
     * 取得單一裝置詳情，並依照維修項目順序載入價格
     */
    public function getDeviceWithPrices($id)
    {
        return DeviceModel::with(['brand', 'prices' => function ($query) {
            // join repair_items 資料表以便使用 sort_order 排序
            $query->join('repair_items', 'device_repair_prices.repair_item_id', '=', 'repair_items.id')
                ->select('device_repair_prices.*')
                ->orderBy('repair_items.sort_order', 'asc');
        }, 'prices.repairItem'])
        ->findOrFail($id);
    }

    /**
     * 建立預約詢問單
     */
    public function createInquiry(array $data)
    {
        return Inquiry::create($data);
    }
}

