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
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = '商店管理';
    protected static ?string $navigationLabel = '商品列表';
    protected static ?int $navigationSort = 2;

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
                                            ->label('上架販售')
                                            ->default(true)
                                            ->inline(false),
                                    ])
                                    ->columns(2),

                                // 1-2. 圖片上傳區塊
                                Forms\Components\Section::make('商品圖片')
                                    ->schema([
                                        Forms\Components\FileUpload::make('images')
                                            ->label('主圖與圖庫 (支援多張拖曳排序)')
                                            ->image()
                                            ->multiple()
                                            ->reorderable()
                                            ->directory('products')
                                            ->panelLayout('grid')
                                            ->columnSpanFull(),
                                    ]),

                                // 1-3. 規格管理 (Repeater)
                                Forms\Components\Section::make('規格與庫存')
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

                                                Forms\Components\FileUpload::make('image')
                                                    ->label('規格圖片')
                                                    ->image()
                                                    ->directory('products/variants')
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(4)
                                            ->defaultItems(1)
                                            ->addActionLabel('新增規格'),
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
                Tables\Columns\ImageColumn::make('images') // 顯示第一張圖
                    ->label('圖片')
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
                    ->sum('variants', 'stock')
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
