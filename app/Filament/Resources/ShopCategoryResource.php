<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShopCategoryResource\Pages;
// use App\Filament\Resources\ShopCategoryResource\RelationManagers;
use App\Models\ShopCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Support\Str;


class ShopCategoryResource extends Resource
{
    protected static ?string $model = ShopCategory::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = '商店管理'; // 建立新群組
    protected static ?string $navigationLabel = '商品分類';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = '商品分類';
    protected static ?string $pluralModelLabel = '商品分類';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)->schema([
                    // 1. 父分類選擇器
                    Forms\Components\Select::make('parent_id')
                        ->label('上層分類')
                        ->relationship(
                            name: 'parent', 
                            titleAttribute: 'name',
                            // ★ 加入查詢過濾邏輯
                            modifyQueryUsing: function (Builder $query, ?ShopCategory $record) {
                                // 如果是「新增」狀態 ($record 為 null)，所有分類都可選，不做限制
                                if (! $record) {
                                    return;
                                }

                                // 如果是「編輯」狀態：
                                // 1. 取得自己 + 所有子孫分類的 ID (這些都不能當作自己的爸爸)
                                // 這是利用我們在 Model 寫好的遞迴方法 getAllChildrenIds()
                                $invalidIds = $record->getAllChildrenIds();

                                // 2. 在選單中排除這些 ID
                                $query->whereNotIn('id', $invalidIds);
                            }
                        )
                        ->searchable()
                        ->preload()
                        ->placeholder('無 (設為最上層分類)'),

                    // 2. 分類名稱
                    Forms\Components\TextInput::make('name')
                        ->label('分類名稱')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Forms\Set $set, ?string $state, ?ShopCategory $record) {
                            // 1. 嘗試標準轉址 (會過濾掉中文)
                            $slug = Str::slug($state);

                            // 2. 如果轉出來是空的 (代表全中文)，或者你想強制加上後綴
                            if (empty($slug)) {
                                // 如果是編輯模式 (有 $record)，就用 ID
                                // 如果是新增模式 (無 $record)，用當下時間戳記末 4 碼當作暫時 ID
                                $suffix = $record ? $record->id : substr(time(), -4);
                                $slug = 'cat-' . $suffix;
                            }

                            $set('slug', $slug);
                        }),

                    Forms\Components\TextInput::make('slug')
                        ->label('網址代稱')
                        ->required(),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('排序')
                        ->numeric()
                        ->default(0),
                ]),

                SpatieMediaLibraryFileUpload::make('category_icon')
                    ->label('分類圖片 (選填)')
                    ->collection('category_icon'),

                Forms\Components\Toggle::make('is_visible')
                    ->label('前台顯示')
                    ->default(true),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('category_icon')
                    ->label('圖片')
                    ->collection('category_icon'),
                Tables\Columns\TextColumn::make('name')
                    ->label('分類名稱')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('上層分類')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_visible')
                    ->label('顯示')
                    ->boolean(),
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
            'index' => Pages\ListShopCategories::route('/'),
            'create' => Pages\CreateShopCategory::route('/create'),
            'edit' => Pages\EditShopCategory::route('/{record}/edit'),
        ];
    }
}
