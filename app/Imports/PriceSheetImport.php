<?php

namespace App\Imports;

use App\Models\DeviceRepairPrice;
use App\Models\DeviceModel;
use App\Models\RepairItem;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class PriceSheetImport implements ToCollection
{
    protected $updateSortOrder;

    public function __construct($updateSortOrder)
    {
        $this->updateSortOrder = $updateSortOrder;
    }

    public function collection(Collection $rows)
    {
        // === 這裡放原本 PriceImport::collection 的所有程式碼 ===
        // 邏輯完全不用變，因為我們是針對「單一工作表」處理

        $headerRow = $rows->first();
        $columnMap = [];
        $repairItems = RepairItem::all();

        foreach ($headerRow as $index => $cellValue) {
            $item = $repairItems->firstWhere('name', $cellValue);
            if ($item) $columnMap[$index] = $item->id;
        }

        $dataRows = $rows->slice(1)->values();

        foreach ($dataRows as $sortIndex => $row) {
            $deviceId = $row[0];
            if (!$deviceId) continue;

            if ($this->updateSortOrder) {
                DeviceModel::where('id', $deviceId)->update(['sort_order' => $sortIndex + 1]);
            }

            foreach ($columnMap as $colIndex => $repairItemId) {
                $price = $row[$colIndex];
                if (is_numeric($price)) {
                    DeviceRepairPrice::updateOrCreate(
                        ['device_model_id' => $deviceId, 'repair_item_id' => $repairItemId],
                        ['price' => (int) $price]
                    );
                }
            }
        }
    }
}

?>
