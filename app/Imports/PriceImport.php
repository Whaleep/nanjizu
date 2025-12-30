<?php

namespace App\Imports;

use App\Models\DeviceRepairPrice;
use App\Models\DeviceModel;
use App\Models\RepairItem;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PriceImport implements WithMultipleSheets
{
    protected $updateSortOrder;

    public function __construct($updateSortOrder = false)
    {
        $this->updateSortOrder = $updateSortOrder;
    }

    public function sheets(): array
    {
        // 回傳一個陣列，代表對每一個 Sheet 都要使用 SheetImport 處理
        // 使用 0 => new SheetImport 代表所有 Sheet 都套用同一套邏輯
        return [
            new PriceSheetImport($this->updateSortOrder),
        ];
    }
}

?>
