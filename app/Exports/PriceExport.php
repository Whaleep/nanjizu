<?php

namespace App\Exports;

use App\Models\DeviceModel;
use App\Models\RepairItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PriceExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $repairItems;
    protected $brandId;     // 新增
    protected $categoryId;  // 新增

    // 建構子接收篩選條件
    public function __construct($brandId = null, $categoryId = null)
    {
        $this->repairItems = RepairItem::orderBy('sort_order', 'asc')->get();
        $this->brandId = $brandId;
        $this->categoryId = $categoryId;
    }

    public function collection()
    {
        $query = DeviceModel::query()->with(['brand', 'deviceCategory', 'prices']);

        // 應用篩選
        if ($this->brandId) {
            $query->where('brand_id', $this->brandId);
        }

        if ($this->categoryId) {
            $query->where('device_category_id', $this->categoryId);
        }

        // 預設依照目前的 sort_order 匯出，方便老闆在此基礎上調整
        return $query->orderBy('sort_order', 'asc')->get();
    }

    public function headings(): array
    {
        $headers = ['ID (勿改)', '品牌', '系列', '型號'];
        foreach ($this->repairItems as $item) {
            $headers[] = $item->name;
        }
        return $headers;
    }

    public function map($device): array
    {
        $row = [
            $device->id,
            $device->brand->name,
            $device->deviceCategory->name ?? '',
            $device->name,
        ];

        foreach ($this->repairItems as $item) {
            $priceRecord = $device->prices->firstWhere('repair_item_id', $item->id);
            $row[] = $priceRecord ? $priceRecord->price : 0;
        }

        return $row;
    }

    // 設定樣式：讓第一列變粗體
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
