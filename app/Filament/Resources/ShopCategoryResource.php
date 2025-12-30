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
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ShopCategoryResource extends Resource
{
    protected static ?string $model = ShopCategory::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = '商店管理'; // 建立新群組
    protected static ?string $navigationLabel = '商品分類';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)->schema([
                    // 1. 父分類選擇器
                    Forms\Components\Select::make('parent_id')
                        ->label('上層分類')
                        ->relationship('parent', 'name')
                        ->searchable()
                        ->placeholder('無 (設為最上層品牌)'),

                    // 2. 分類名稱
                    Forms\Components\TextInput::make('name')
                        ->label('分類名稱')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn(Forms\Set $set, ?string $state) =>
                        $set('slug', Str::slug($state))),

                    Forms\Components\TextInput::make('slug')
                        ->label('網址代稱')
                        ->required(),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('排序')
                        ->numeric()
                        ->default(0),
                ]),

                Forms\Components\FileUpload::make('image')
                    ->label('分類圖片 (選填)')
                    ->image()
                    ->directory('shop-categories'),

                Forms\Components\Toggle::make('is_visible')
                    ->label('前台顯示')
                    ->default(true),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('圖片'),
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

?>
