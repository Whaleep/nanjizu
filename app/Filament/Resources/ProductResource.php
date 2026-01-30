<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Blocks\ContentBlocks;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Tabs; // 引入 Tabs 元件
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = '商店管理';
    protected static ?string $navigationLabel = '商品列表';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = '商品';
    protected static ?string $pluralModelLabel = '商品';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // 使用 Tabs 元件包覆所有內容
                Tabs::make('Product Tabs')
                    ->tabs([
                        // === Tab 1: 基本資訊、圖片、規格 ===
                        Tabs\Tab::make('基本資訊 & 規格')
                            ->icon('heroicon-m-information-circle')
                            ->schema([

                                // 1-1. 基本資料區塊
                                Forms\Components\Section::make('基本資料')
                                    ->schema([
                                        Forms\Components\Select::make('shop_category_id')
                                            ->label('所屬分類')
                                            ->options(function () {
                                                return \App\Models\ShopCategory::all()->mapWithKeys(function ($category) {
                                                    return [$category->id => $category->full_name];
                                                });
                                            })
                                            ->searchable()
                                            ->required()
                                            ->columnSpan(1),

                                        Forms\Components\Select::make('tags')
                                            ->relationship('tags', 'name')
                                            ->label('商品標籤')
                                            ->multiple()
                                            ->preload()
                                            ->searchable()
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('name')
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn(Forms\Set $set, ?string $state) => $set('slug', Str::slug($state))),
                                                Forms\Components\TextInput::make('slug')->required(),
                                            ])
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('name')
                                            ->label('商品名稱')
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn(Forms\Set $set, ?string $state) => $set('slug', Str::slug($state)))
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('slug')
                                            ->label('網址代稱')
                                            ->unique(ignoreRecord: true)
                                            ->required()
                                            ->columnSpan(1),

                                        // 新增：商品摘要
                                        Forms\Components\Textarea::make('excerpt')
                                            ->label('商品摘要 (顯示於標題下方)')
                                            ->rows(2)
                                            ->columnSpanFull()
                                            ->helperText('簡短說明賣點，例如：送保貼 | 台灣公司貨 | 1年保固'),

                                        Forms\Components\Toggle::make('is_active')
                                            ->label('上架狀態')
                                            ->default(true)
                                            ->inline(false),
                                        Forms\Components\Toggle::make('is_sellable')
                                            ->label('可單獨販售')
                                            ->helperText('若關閉，則此商品僅能作為贈品贈送')
                                            ->default(true)
                                            ->inline(false),
                                    ])
                                    ->columns(2),

                                // 1-2. 圖片上傳區塊
                                Forms\Components\Section::make('商品圖片')
                                    ->schema([
                                        SpatieMediaLibraryFileUpload::make('product_images')
                                            ->label('主圖與圖庫 (支援多張拖曳排序)')
                                            ->collection('product_images')
                                            ->multiple()
                                            ->reorderable()
                                            ->panelLayout('grid')
                                            ->columnSpanFull(),
                                    ]),

                                // 1-3. 規格管理 (Repeater)
                                Forms\Components\Section::make('規格與庫存')
                                    ->headerActions([
                                        // Action A: 從選項生成規格
                                        Forms\Components\Actions\Action::make('generateVariants')
                                            ->label('從選項生成規格')
                                            ->icon('heroicon-m-arrows-right-left')
                                            ->requiresConfirmation()
                                            ->modalHeading('確定要生成規格嗎？')
                                            ->modalDescription('這將會根據您的「視覺化點擊介面選項」重新產生所有可能的規格組合。既有的規格列表將會被覆蓋。')
                                            ->modalSubmitActionLabel('確認生成')
                                            ->action(function (Forms\Get $get, Forms\Set $set) {
                                                $options = $get('options') ?? [];
                                                if (empty($options)) {
                                                    // 如果沒有選項，提示使用者
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('請先定義視覺化選項')
                                                        ->warning()
                                                        ->send();
                                                    return;
                                                }

                                                // 1. 解析選項，準備進行排列組合
                                                // options 結構: [{name: 'Color', values: [{label: 'Red', value: '#F00'}, ...]}, ...]
                                                $optionData = [];
                                                foreach ($options as $opt) {
                                                    if (!empty($opt['values'])) {
                                                        $optionData[] = [
                                                            'name' => $opt['name'],
                                                            'values' => $opt['values'] // array of {label, value}
                                                        ];
                                                    }
                                                }

                                                if (empty($optionData)) return;

                                                // 2. 笛卡兒積 (Cartesian Product) 遞迴函數
                                                $cartesian = function ($input) use (&$cartesian) {
                                                    $result = [[]];
                                                    foreach ($input as $key => $values) {
                                                        $append = [];
                                                        foreach ($result as $product) {
                                                            foreach ($values as $item) {
                                                                $product[$key] = $item;
                                                                $append[] = $product;
                                                            }
                                                        }
                                                        $result = $append;
                                                    }
                                                    return $result;
                                                };

                                                // 整理要進行組合的陣列： [[val1, val2], [val3, val4]]
                                                $inputForCartesian = [];
                                                foreach ($optionData as $opt) {
                                                    $inputForCartesian[$opt['name']] = $opt['values'];
                                                }

                                                $combinations = $cartesian($inputForCartesian);

                                                // 3. 轉換為 Variants Repeater 格式
                                                $newVariants = [];
                                                foreach ($combinations as $combo) {
                                                    // $combo 範例: ['Color' => {label: 'Red', value: '#F00'}, 'Size' => {label: 'S', value: 'S'}]

                                                    // 屬性 Key-Value 對 (儲存給 attributes欄位)
                                                    $attributes = [];
                                                    $nameParts = [];

                                                    foreach ($combo as $optName => $valObj) {
                                                        $attributes[$optName] = $valObj['value'];
                                                        $nameParts[] = $valObj['label']; // 使用 label (顯示文字) 來組合成商品名稱
                                                    }

                                                    $variantName = implode(' / ', $nameParts);

                                                    $newVariants[] = [
                                                        'name' => $variantName,
                                                        'price' => 0,
                                                        'stock' => 0,
                                                        'sku' => '',
                                                        'image' => null, // 預設無圖
                                                        'attributes' => $attributes,
                                                    ];
                                                }

                                                // 4. 設定回表單
                                                $set('variants', $newVariants);

                                                \Filament\Notifications\Notification::make()
                                                    ->title('規格生成完畢')
                                                    ->success()
                                                    ->send();
                                            }),

                                        // Action B: 批次修改價格
                                        Forms\Components\Actions\Action::make('bulkPriceUpdate')
                                            ->label('批次修改價格')
                                            ->icon('heroicon-m-currency-dollar')
                                            ->form([
                                                Forms\Components\TextInput::make('price')
                                                    ->label('統一價格')
                                                    ->numeric()
                                                    ->required()
                                                    ->prefix('$'),
                                            ])
                                            ->action(function (array $data, Forms\Get $get, Forms\Set $set) {
                                                $currentVariants = $get('variants') ?? [];

                                                if (empty($currentVariants)) {
                                                    return;
                                                }

                                                $newPrice = $data['price'];

                                                // 更新每一列的 price
                                                foreach ($currentVariants as $key => $variant) {
                                                    $currentVariants[$key]['price'] = $newPrice;
                                                }

                                                $set('variants', $currentVariants);

                                                \Filament\Notifications\Notification::make()
                                                    ->title('價格已更新')
                                                    ->success()
                                                    ->send();
                                            }),

                                        // Action C: 指定選項修改價格 (如: 所有 XL 號)
                                        Forms\Components\Actions\Action::make('updateOptionPrice')
                                            ->label('指定選項修改價格')
                                            ->icon('heroicon-m-adjustments-horizontal')
                                            ->form(function (Forms\Get $get) {
                                                // 動態取得目前的選項結構
                                                $options = $get('options') ?? [];
                                                // 整理出 Name 清單: ['Color' => 'Color', 'Size' => 'Size']
                                                $optionNames = [];
                                                foreach ($options as $opt) {
                                                    if (!empty($opt['name'])) {
                                                        $optionNames[$opt['name']] = $opt['name'];
                                                    }
                                                }

                                                return [
                                                    Forms\Components\Select::make('target_option')
                                                        ->label('選擇選項名稱 (例如 Size)')
                                                        ->options($optionNames)
                                                        ->required()
                                                        ->live() // 讓下一個欄位可以連動
                                                        ->afterStateUpdated(fn(Forms\Set $set) => $set('target_value', null)),

                                                    Forms\Components\Select::make('target_value')
                                                        ->label('選擇選項值 (例如 XL)')
                                                        ->options(function (Forms\Get $get) use ($options) {
                                                            $targetName = $get('target_option');
                                                            if (!$targetName) return [];

                                                            // 找出該 Option 的 Values
                                                            $targetOpt = collect($options)->firstWhere('name', $targetName);
                                                            if (!$targetOpt || empty($targetOpt['values'])) return [];

                                                            // 回傳 ['XL' => 'XL', 'L' => 'L']
                                                            $values = [];
                                                            foreach ($targetOpt['values'] as $v) {
                                                                $values[$v['value']] = $v['label'] . " ({$v['value']})";
                                                            }
                                                            return $values;
                                                        })
                                                        ->required()
                                                        ->disabled(fn(Forms\Get $get) => !$get('target_option')),

                                                    Forms\Components\TextInput::make('price')
                                                        ->label('設定價格')
                                                        ->numeric()
                                                        ->required()
                                                        ->prefix('$'),
                                                ];
                                            })
                                            ->action(function (array $data, Forms\Get $get, Forms\Set $set) {
                                                $currentVariants = $get('variants') ?? [];
                                                if (empty($currentVariants)) return;

                                                $targetOption = $data['target_option'];
                                                $targetValue = $data['target_value'];
                                                $newPrice = $data['price'];
                                                $updatedCount = 0;

                                                foreach ($currentVariants as $key => $variant) {
                                                    // 檢查 attributes
                                                    $attrs = $variant['attributes'] ?? [];
                                                    // 如果該變體的屬性中，有包含目標選項且值相符
                                                    if (isset($attrs[$targetOption]) && $attrs[$targetOption] == $targetValue) {
                                                        $currentVariants[$key]['price'] = $newPrice;
                                                        $updatedCount++;
                                                    }
                                                }

                                                $set('variants', $currentVariants);

                                                \Filament\Notifications\Notification::make()
                                                    ->title("已更新 {$updatedCount} 筆規格價格")
                                                    ->success()
                                                    ->send();
                                            }),

                                    ])
                                    ->schema([
                                        Forms\Components\Repeater::make('variants')
                                            ->relationship()
                                            ->label('規格列表')
                                            ->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->label('規格名稱')
                                                    ->placeholder('例：原廠 / 256GB')
                                                    ->required(),

                                                Forms\Components\TextInput::make('price')
                                                    ->label('價格')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->required(),

                                                Forms\Components\TextInput::make('stock')
                                                    ->label('庫存')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->required(),

                                                Forms\Components\TextInput::make('sku')
                                                    ->label('料號 (SKU)')
                                                    ->placeholder('可選'),

                                                Forms\Components\SpatieMediaLibraryFileUpload::make('image')
                                                    ->label('規格圖片')
                                                    ->collection('variant_image')
                                                    ->image()
                                                    ->columnSpanFull(),

                                                Forms\Components\KeyValue::make('attributes')
                                                    ->label('規格屬性 (對應點擊介面選項)')
                                                    ->keyLabel('規格名稱 (如: 顏色)')
                                                    ->valueLabel('規格值 (如: Red, #FF0000)')
                                                    ->helperText('請填寫對應「點擊介面選項」的值，例如 顏色: #FF0000, 尺寸: M')
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(4)
                                            ->defaultItems(1)
                                            ->addActionLabel('新增規格'),
                                    ]),

                                // 1-4. 視覺化點擊介面選項
                                Forms\Components\Section::make('視覺化點擊介面選項 (Visual Options)')
                                    ->description('定義點擊介面顯示的規格類型 (如顏色圈圈、尺寸方塊)')
                                    ->schema([
                                        Forms\Components\Repeater::make('options')
                                            ->label('選項群組')
                                            ->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->label('選項名稱 (如: 顏色, 尺寸)')
                                                    ->required()
                                                    ->columnSpan(1),

                                                Forms\Components\Select::make('type')
                                                    ->label('顯示類型')
                                                    ->options([
                                                        'text' => '文字方塊 (Text Chips)',
                                                        'color' => '顏色圈圈 (Color Swatches)',
                                                        'image' => '圖片方塊 (Image Chips)',
                                                    ])
                                                    ->required()
                                                    ->default('text')
                                                    ->columnSpan(1),

                                                Forms\Components\Repeater::make('values')
                                                    ->label('選項值')
                                                    ->schema([
                                                        Forms\Components\TextInput::make('label')
                                                            ->label('顯示文字 (如: 紅色, XL)')
                                                            ->required(),
                                                        Forms\Components\TextInput::make('value')
                                                            ->label('實際值 (如: #FF0000, XL)')
                                                            ->required(),
                                                        // 選項值專屬圖片 (例如紅色的代表圖)
                                                        Forms\Components\FileUpload::make('image')
                                                            ->label('代表圖 (可選)')
                                                            ->image()
                                                            ->directory('products/options')
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->columns(2)
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2)
                                            ->addActionLabel('新增選項群組')
                                            ->defaultItems(0),
                                    ]),
                            ]),

                        // === Tab 2: 右側描述 (改用 Tiptap) ===
                        Tabs\Tab::make('商品描述 (右側)')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                Builder::make('description') // 改用 Builder
                                    ->label('自訂區塊')
                                    ->blocks(ContentBlocks::make()) // <--- 呼叫共用設定
                                    ->collapsible()
                                    ->collapsed(),
                            ]),

                        // === Tab 3: 下方排版 (Builder) ===
                        Tabs\Tab::make('下方排版 (滿版)')
                            ->icon('heroicon-m-cube')
                            ->schema([
                                Builder::make('content')
                                    ->label('自訂區塊')
                                    ->blocks(ContentBlocks::make()) // <--- 呼叫共用設定
                                    ->collapsible()
                                    ->collapsed(),
                            ]),
                    ])
                    ->columnSpanFull(), // 讓 Tabs 佔滿整寬度
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('product_images') // 顯示第一張圖
                    ->label('圖片')
                    ->collection('product_images')
                    ->circular()
                    ->stacked(),

                Tables\Columns\TextColumn::make('name')
                    ->label('商品名稱')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        // 滑鼠移過去時，顯示完整的內容
                        return $column->getState();
                    }),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('分類')
                    ->sortable(),

                Tables\Columns\TextColumn::make('variants_min_price')
                    ->label('價格')
                    ->money('TWD')
                    ->getStateUsing(function (Product $record) {
                        return $record->variants->min('price');
                    })
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('variants_sum_stock')
                    ->label('總庫存')
                    ->getStateUsing(fn(Product $record) => $record->variants->sum('stock'))
                    ->alignEnd(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('上架')
                    ->boolean(),

                Tables\Columns\TextColumn::make('view_count')
                    ->label('瀏覽')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('sold_count')
                    ->label('已售')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('shop_category_id')
                    ->relationship('category', 'name')
                    ->label('分類篩選'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label("")
                    ->tooltip('編輯商品'),
                Tables\Actions\ReplicateAction::make()
                    ->label("")
                    ->tooltip('複製商品')
                    ->modalHeading('複製商品?')
                    ->modalSubmitActionLabel('確認複製')
                    ->modalCancelActionLabel('取消')
                    ->beforeReplicaSaved(function (Product $replica) {
                        $replica->name = $replica->name . ' (Copy)';
                        $replica->slug = Str::slug($replica->name . '-' . time()); // 確保唯一性
                        $replica->is_active = false; // 預設下架
                        $replica->image = null; // 複製時不要帶著圖片，避免更新圖片時誤刪原商品圖片
                    })
                    ->after(function (Product $replica, Product $record) {
                        // 手動複製規格 (Deep Copy Variants)
                        foreach ($record->variants as $variant) {
                            $replica->variants()->create([
                                'name' => $variant->name,
                                'price' => $variant->price,
                                'stock' => $variant->stock,
                                'sku' => $variant->sku ? $variant->sku . '-COPY' : null,
                                'image' => null, // 規格也不要帶著圖片
                                'attributes' => $variant->attributes,
                            ]);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('商品複製成功')
                            ->body('已複製商品與所有規格，不含圖片，預設為下架狀態。')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label("")
                    ->tooltip('刪除商品')
                    ->modalHeading('刪除商品')
                    ->modalSubmitActionLabel('確認刪除')
                    ->modalCancelActionLabel('取消'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
