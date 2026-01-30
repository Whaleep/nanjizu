<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromotionResource\Pages;
use App\Models\Promotion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Tabs;

class PromotionResource extends Resource
{
    protected static ?string $model = Promotion::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationGroup = '商店管理';
    protected static ?string $navigationLabel = '特惠活動管理';
    protected static ?string $modelLabel = '特惠活動';
    protected static ?string $pluralModelLabel = '特惠活動';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Promotion Tabs')
                    ->contained(true) 
                    ->tabs([
                        // Tab 1: 基本設定
                        Tabs\Tab::make('基本設定')
                            ->schema([
                                Forms\Components\Section::make('基本資訊')->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label('活動名稱')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(2),
                                    Forms\Components\Textarea::make('description')
                                        ->label('活動描述')
                                        ->columnSpanFull(),
                                    Forms\Components\Toggle::make('is_active')
                                        ->label('啟用')
                                        ->default(true),
                                    Forms\Components\Toggle::make('show_badge')
                                        ->label('顯示活動標籤')
                                        ->helperText('關閉後，商品列表與詳情頁將不會宣傳此活動。')
                                        ->default(true),
                                    Forms\Components\TextInput::make('priority')
                                        ->label('優先級')
                                        ->numeric()
                                        ->default(0)
                                        ->helperText('數字越大優先權越高'),
                                ])->columns(2),

                                Forms\Components\Section::make('時間範圍')->schema([
                                    Forms\Components\DateTimePicker::make('start_at')
                                        ->label('開始時間'),
                                    Forms\Components\DateTimePicker::make('end_at')
                                        ->label('結束時間'),
                                ])->columns(2),
                            ]),

                        // Tab 2: 規則與動作
                        Tabs\Tab::make('規則與動作')
                            ->schema([
                                Forms\Components\Section::make('觸發條件與模式')->schema([
                                    Forms\Components\Select::make('type')
                                        ->label('促銷模式')
                                        ->options([
                                            'direct' => '直接折扣 (商品頁面直接改價)',
                                            'threshold_cart' => '條件特惠 (購物車結算)',
                                        ])
                                        ->required()
                                        ->live(),

                                    Forms\Components\Select::make('action_type')
                                        ->label('執行動作')
                                        ->options([
                                            'percent' => '打折 (%)',
                                            'fixed' => '折現 ($)',
                                            'gift' => '送贈品 (選「條件特惠」才有效)',
                                        ])
                                        ->required()
                                        ->live(),
                                    
                                    Forms\Components\Placeholder::make('invalid_combo_warning')
                                        ->hiddenLabel()
                                        ->content('⚠️ 系統限制：「直接折扣」模式無法使用「送贈品」。請改用「條件特惠」或選擇其他動作。')
                                        ->visible(fn(Forms\Get $get) => $get('type') === 'direct' && $get('action_type') === 'gift')
                                        ->columnSpanFull()
                                        ->extraAttributes(['class' => 'text-danger-600 bg-danger-50 p-4 rounded border border-danger-200']),

                                    Forms\Components\TextInput::make('action_value')
                                        ->label(fn(Forms\Get $get) => $get('action_type') === 'percent' ? '打折數 (例如 10 代表 9 折/扣除 10%)' : '折抵金額')
                                        ->numeric()
                                        ->visible(fn(Forms\Get $get) => in_array($get('action_type'), ['percent', 'fixed']))
                                        ->required(fn(Forms\Get $get) => in_array($get('action_type'), ['percent', 'fixed']))
                                        ->suffix(fn(Forms\Get $get) => $get('action_type') === 'percent' ? '%' : '$'),
                                    
                                    // --- 累計開關 (只在折現時出現) ---
                                    Forms\Components\Toggle::make('is_repeatable')
                                        ->label('累計折抵 (每滿 $X 折 $Y)')
                                        ->visible(fn(Forms\Get $get) => $get('type') === 'threshold_cart' && $get('action_type') === 'fixed')
                                        ->default(false)
                                        ->helperText('開啟後，例如「每滿 1000 折 100」，買 5000 會折 500。若關閉則不論買多少只折一次。')
                                        ->columnSpanFull(),
                                ])->columns(2),

                                Forms\Components\Section::make('門檻與上限')
                                    ->visible(fn(Forms\Get $get) => $get('type') === 'threshold_cart')
                                    ->schema([
                                        Forms\Components\Select::make('threshold_type')
                                            ->label('計算門檻單位')
                                            ->options([
                                                'amount' => '依消費金額 (例如：滿 5000)',
                                                'quantity' => '依購買數量 (例如：買 3 件)',
                                            ])
                                            ->default('amount')
                                            ->required()
                                            ->live(), // 因為 unit_cost 的 label 會依賴它，所以要 live
                                            
                                        Forms\Components\TextInput::make('min_threshold')
                                            // 動態標籤：讓使用者知道這就是「每滿多少」的基準
                                            ->label(fn(Forms\Get $get) => 
                                                $get('action_type') === 'fixed' && $get('is_repeatable') 
                                                ? '折抵級距 (每滿)' 
                                                : '最低觸發門檻'
                                            )
                                            ->numeric()
                                            ->default(0)
                                            ->prefix(fn(Forms\Get $get) => $get('threshold_type') === 'amount' ? '$' : null)
                                            ->suffix(fn(Forms\Get $get) => $get('threshold_type') === 'quantity' ? '件' : null)
                                            ->helperText(fn(Forms\Get $get) => 
                                                '未達此數值特惠不會生效，但前端會顯示「再買 X 即可享優惠」的提示。'
                                            ),

                                        Forms\Components\TextInput::make('max_gift_count')
                                            ->label(fn(Forms\Get $get) => match ($get('action_type')) {
                                                'gift' => '贈品領取上限 (件)',
                                                'fixed' => '折扣次數上限 (次)',
                                                default => '上限設定'
                                            })
                                            ->numeric()
                                            ->placeholder('無限制')
                                            // 打折 (%) 模式下，上限通常無意義 (除非你要做很複雜的 logic)，這裡先隱藏避免混淆
                                            ->visible(fn(Forms\Get $get) => in_array($get('action_type'), ['gift', 'fixed']))
                                            ->helperText(fn(Forms\Get $get) => match ($get('action_type')) {
                                                'gift' => '限制這張訂單「最多」能拿幾個贈品。',
                                                'fixed' => '限制「累計折抵」最多折幾次。例如上限 5 次，買再多也只折 5 倍。',
                                                default => ''
                                            }),
                                    ])->columns(2),
                            ]),

                        // Tab 3: 適用範圍 & 贈品
                        Tabs\Tab::make('適用範圍 & 贈品')
                            ->schema([
                                Forms\Components\Section::make('適用對象')
                                    ->description('設定此活動包含哪些商品，留空或選擇「全部商品」則全館適用。')
                                    ->schema([
                                    Forms\Components\Select::make('scope')
                                        ->label('適用範圍')
                                        ->options([
                                            'all' => '全部商品',
                                            'product' => '指定商品',
                                            'category' => '指定分類',
                                            'tag' => '指定標籤',
                                        ])
                                        ->required()
                                        ->live(),

                                    Forms\Components\Select::make('products')
                                        ->label('選擇商品')
                                        ->relationship('products', 'name')
                                        ->multiple()
                                        ->preload()
                                        ->visible(fn(Forms\Get $get) => $get('scope') === 'product'),

                                    Forms\Components\Select::make('categories')
                                        ->label('選擇分類')
                                        ->relationship('categories', 'name')
                                        ->multiple()
                                        ->preload()
                                        ->visible(fn(Forms\Get $get) => $get('scope') === 'category'),

                                    Forms\Components\Select::make('productTags')
                                        ->label('選擇標籤')
                                        ->relationship('productTags', 'name')
                                        ->multiple()
                                        ->preload()
                                        ->visible(fn(Forms\Get $get) => $get('scope') === 'tag'),
                                ]),

                                // 修正處：這裡改成 Section，且增加 visible 判斷
                                Forms\Components\Section::make('贈品池與成本設定')
                                    ->visible(fn(Forms\Get $get) => $get('action_type') === 'gift')
                                    ->schema([
                                        Forms\Components\Repeater::make('promotionGifts')
                                            ->relationship('promotionGifts')
                                            ->label('贈品清單')
                                            ->schema([
                                                Forms\Components\Select::make('product_variant_id')
                                                    ->label('選擇贈品規格')
                                                    ->options(function () {
                                                        // 使用 mapWithKeys 組合名稱
                                                        return \App\Models\ProductVariant::with('product')
                                                            ->whereHas('product', fn($q) => $q->where('is_active', true)) // 建議加個過濾
                                                            ->get()
                                                            ->mapWithKeys(function ($variant) {
                                                                return [$variant->id => "{$variant->product->name} - {$variant->name}"];
                                                            });
                                                    })
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()->columnSpan(2),
                                                
                                                Forms\Components\TextInput::make('unit_cost')
                                                    ->label(fn(Forms\Get $get) => $get('../../threshold_type') === 'quantity' ? '扣除件數額度' : '扣除金額額度')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->required()
                                                    ->columnSpan(2)
                                                    ->helperText('選 1 個此贈品，需要消耗多少消費累積額度。'),
                                            ])
                                            ->grid(2)
                                            ->columnSpanFull()
                                            ->addActionLabel('新增可選贈品'),
                                    ]),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('活動名稱')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('type_summary')
                    ->label('活動模式')
                    ->badge()
                    ->state(function (Promotion $record): string {
                        if ($record->type === 'direct') return '直接改價';
                        $action = match($record->action_type) {
                            'percent' => '打折',
                            'fixed' => '折現',
                            'gift' => '贈品',
                            default => $record->action_type
                        };
                        return "條件: {$action}";
                    })
                    ->color(fn (string $state): string => str_contains($state, '贈品') ? 'success' : 'primary'),

                Tables\Columns\TextColumn::make('threshold_desc')
                    ->label('門檻規則')
                    ->state(function (Promotion $record): string {
                        if ($record->type === 'direct') return '-';
                        $unit = $record->threshold_type === 'quantity' ? '件' : '$';
                        return "滿 {$unit}{$record->min_threshold}";
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('啟用')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('active_period')
                    ->label('有效期間')
                    ->state(function (Promotion $record): string {
                        if (!$record->start_at && !$record->end_at) return '永久有效';
                        $start = $record->start_at ? $record->start_at->format('Y/m/d') : '即起';
                        $end = $record->end_at ? $record->end_at->format('Y/m/d') : '無限期';
                        return "{$start} ~ {$end}";
                    })
                    ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall),
            ])
            ->defaultSort('priority', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromotions::route('/'),
            'create' => Pages\CreatePromotion::route('/create'),
            'edit' => Pages\EditPromotion::route('/{record}/edit'),
        ];
    }
}