<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
// use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;
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
                Forms\Components\Group::make()->schema([
                    // 左側：基本資訊
                    Forms\Components\Section::make('基本資訊')->schema([
                        Forms\Components\Select::make('shop_category_id')
                            // ->relationship('category', 'name')
                            ->label('所屬分類')
                            ->options(function () {
                                // 抓出所有分類，並轉成 "ID => 全名" 的陣列
                                return \App\Models\ShopCategory::all()->mapWithKeys(function ($category) {
                                    return [$category->id => $category->full_name];
                                });
                            })
                            ->searchable()
                            // ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('name')
                            ->label('商品名稱')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Forms\Set $set, ?string $state) =>
                            $set('slug', Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->label('網址代稱')
                            ->unique(ignoreRecord: true)
                            ->required(),

                        Forms\Components\Select::make('tags')
                            ->relationship('tags', 'name') // 關聯到 tags 模型
                            ->label('商品標籤')
                            ->multiple() // 支援多選
                            ->preload()  // 預先載入選項
                            ->searchable()
                            ->createOptionForm([ // 允許直接在這裡新增標籤 (不用跳去標籤管理頁)
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn(Forms\Set $set, ?string $state) =>
                                    $set('slug', Str::slug($state))),
                                Forms\Components\TextInput::make('slug')->required(),
                            ]),

                        Forms\Components\RichEditor::make('description')
                            ->label('商品描述')
                            ->columnSpanFull(),
                    ]),

                    // 左側：規格管理 (Repeater)
                    Forms\Components\Section::make('商品規格與庫存')->schema([
                        Forms\Components\Repeater::make('variants')
                            ->relationship() // 自動關聯到 ProductVariant
                            ->label('規格列表')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('規格名稱')
                                    ->placeholder('例如：原廠 / 256GB / 紅色')
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
                            ])
                            ->columns(4) // 讓規格欄位橫排顯示
                            ->defaultItems(1) // 預設至少有一組規格
                            ->addActionLabel('新增一種規格'),
                    ]),
                ])->columnSpan(2),

                Forms\Components\Group::make()->schema([
                    // 右側：圖片與狀態
                    Forms\Components\Section::make('商品圖片')->schema([
                        Forms\Components\FileUpload::make('image')
                            ->label('主圖')
                            ->image()
                            ->directory('products'),
                    ]),

                    Forms\Components\Section::make('設定')->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('上架販售')
                            ->default(true),
                    ]),
                ])->columnSpan(1),

            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('圖片'),

                Tables\Columns\TextColumn::make('name')
                    ->label('商品名稱')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('分類')
                    ->sortable(),

                // 顯示價格範圍或最低價
                Tables\Columns\TextColumn::make('variants_min_price')
                    ->label('價格')
                    ->money('TWD')
                    ->getStateUsing(function (Product $record) {
                        // 取得該商品底下所有規格的最低價
                        return $record->variants->min('price');
                    })
                    ->alignEnd(),

                // 顯示總庫存
                Tables\Columns\TextColumn::make('variants_sum_stock')
                    ->label('總庫存')
                    ->sum('variants', 'stock'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('上架')
                    ->boolean(),

                Tables\Columns\TextColumn::make('view_count')
                    ->label('瀏覽')
                    ->sortable()
                    ->toggleable(), // 允許隱藏

                Tables\Columns\TextColumn::make('sold_count')
                    ->label('已售')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('shop_category_id')
                    ->relationship('category', 'name')
                    ->label('分類篩選'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
