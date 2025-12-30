<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Exports\PriceExport;
use App\Imports\PriceImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Models\Brand;
use App\Models\DeviceCategory;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\DeviceModel;
use App\Models\RepairItem;

class ManagePricing extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = '批次價格調整';
    protected static ?string $title = '批次價格調整 (Excel)';
    protected static ?string $navigationGroup = '維修資料庫';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.manage-pricing';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    // 這裡只定義「匯入」的表單
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('file')
                    ->label('上傳 Excel 檔案')
                    ->disk('local')
                    ->directory('temp-imports')
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                    ->required(),

                // === 新增：排序開關 ===
                Forms\Components\Checkbox::make('update_sort_order')
                    ->label('依照此 Excel 的列順序更新「機種排序」')
                    ->helperText('若勾選，系統將會把 Excel 第一支手機排在最前面，以此類推。請確認 Excel 內的順序是您想要的。')
                    ->default(false),
                // ====================
            ])
            ->statePath('data');
    }

    // 定義「下載」按鈕，改為帶有表單的 Action
    public function downloadAction(): Action
    {
        return Action::make('download')
            ->label('篩選並下載報價單')
            ->color('success')
            ->icon('heroicon-o-arrow-down-tray')
            ->form([
                // 篩選品牌
                Forms\Components\Select::make('brand_id')
                    ->label('只匯出特定品牌')
                    ->options(Brand::pluck('name', 'id'))
                    ->searchable()
                    ->live(), // 開啟連動

                // 篩選系列
                Forms\Components\Select::make('category_id')
                    ->label('只匯出特定系列')
                    ->options(function (Forms\Get $get) {
                        $brandId = $get('brand_id');
                        if (!$brandId) return [];
                        return DeviceCategory::where('brand_id', $brandId)->pluck('name', 'id');
                    })
                    ->placeholder('全部系列'),
            ])
            ->action(function (array $data) {
                // 傳入篩選條件給 Export
                return Excel::download(
                    new PriceExport($data['brand_id'] ?? null, $data['category_id'] ?? null),
                    'repair_prices_' . date('Y-m-d_His') . '.xlsx'
                );
            });
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $filePath = $data['file'];
        $updateSort = $data['update_sort_order'] ?? false; // 取得勾選狀態

        try {
            $fullPath = storage_path('app/' . $filePath);

            // 傳入 $updateSort 參數給 Import
            Excel::import(new PriceImport($updateSort), $fullPath);

            Notification::make()
                ->title('更新成功！')
                ->body($updateSort ? '價格與排序已更新' : '價格已更新')
                ->success()
                ->send();

            $this->form->fill();
            Storage::disk('local')->delete($filePath);
        } catch (\Exception $e) {
            Notification::make()
                ->title('匯入失敗')
                ->body('錯誤訊息：' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function syncTemplateAction(): Action
    {
        return Action::make('syncTemplate')
            ->label('依照上傳範本更新價格') // 按鈕名稱
            ->color('warning') // 用橘色區隔
            ->icon('heroicon-o-arrow-path')
            ->form([
                Forms\Components\FileUpload::make('template_file')
                    ->label('上傳您的 Excel 範本')
                    ->helperText('系統將會讀取此檔案，將資料庫最新的價格填入對應欄位，並保留您的格式與分頁。')
                    ->disk('local')
                    ->directory('temp-sync')
                    ->required(),
            ])
            ->action(function (array $data) {
                $filePath = storage_path('app/' . $data['template_file']);

                // 1. 讀取 Excel 檔案
                $spreadsheet = IOFactory::load($filePath);

                // 2. 準備快取資料 (避免迴圈內重複查詢 DB)
                $repairItems = RepairItem::all(); // 所有維修項目
                // 預先載入所有裝置價格： [device_id => [repair_item_id => price]]
                $allPrices = \App\Models\DeviceRepairPrice::all()
                    ->groupBy('device_model_id')
                    ->map(function ($items) {
                        return $items->pluck('price', 'repair_item_id');
                    });

                // 3. 遍歷所有工作表 (Sheets)
                foreach ($spreadsheet->getAllSheets() as $sheet) {
                    $rows = $sheet->toArray(); // 轉成陣列方便處理

                    if (count($rows) < 1) continue;

                    // 解析標題列 (Row 0)
                    $headerRow = $rows[0];
                    $colMap = []; // [Column Index => Repair Item ID]

                    foreach ($headerRow as $colIndex => $cellValue) {
                        $item = $repairItems->firstWhere('name', trim($cellValue));
                        if ($item) {
                            $colMap[$colIndex] = $item->id;
                        }
                    }

                    // 如果這一頁沒有任何維修項目標題，就跳過 (可能是說明頁)
                    if (empty($colMap)) continue;

                    // 遍歷資料列 (從 Row 1 開始)
                    // 注意：Excel 行號是從 1 開始，陣列索引是從 0 開始
                    // $sheet->setCellValueByColumnAndRow($col, $row, $value)
                    // Col 從 1 開始, Row 從 1 開始

                    for ($r = 1; $r < count($rows); $r++) {
                        $rowData = $rows[$r];
                        $deviceId = $rowData[0]; // 假設 ID 在第一欄

                        if (!$deviceId || !isset($allPrices[$deviceId])) continue;

                        $devicePrices = $allPrices[$deviceId];

                        // 填入價格
                        foreach ($colMap as $colIndex => $repairItemId) {
                            if (isset($devicePrices[$repairItemId])) {
                                // +1 因為 PhpSpreadsheet 的 Col/Row 都是從 1 開始
                                // 陣列 $r 是 1 (第二列)，所以在 Excel 是 Row 2
                                // 陣列 $colIndex 是 3，Excel 是 Column 4
                                $sheet->setCellValue([$colIndex + 1, $r + 1], $devicePrices[$repairItemId]);
                            }
                        }
                    }
                }

                // 4. 存檔並下載
                $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
                $tempFilename = 'synced_' . date('Ymd_His') . '.xlsx';
                $tempPath = storage_path('app/temp-sync/' . $tempFilename);
                $writer->save($tempPath);

                // 刪除上傳的原檔
                Storage::disk('local')->delete($data['template_file']);

                return response()->download($tempPath)->deleteFileAfterSend();
            });
    }
}
