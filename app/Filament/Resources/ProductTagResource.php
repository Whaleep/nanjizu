<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductTagResource\Pages;
use App\Filament\Resources\ProductTagResource\RelationManagers;
use App\Models\ProductTag;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductTagResource extends Resource
{
    protected static ?string $model = ProductTag::class;

    protected static ?string $navigationIcon = 'heroicon-o-hashtag'; // hashtag 圖示

    protected static ?string $navigationGroup = '商店管理'; // 歸類在商店管理

    protected static ?string $navigationLabel = '商品標籤';

    protected static ?int $navigationSort = 3; // 排在商品列表後面

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()->schema([
                    // 1. 標籤名稱 (自動產生 Slug)
                    Forms\Components\TextInput::make('name')
                        ->label('標籤名稱 (如: 電池, 螢幕)')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn(Forms\Set $set, ?string $state) =>
                        $set('slug', Str::slug($state))),

                    // 2. 網址代稱
                    Forms\Components\TextInput::make('slug')
                        ->label('網址代稱')
                        ->required()
                        ->unique(ignoreRecord: true),

                    // 3. 顏色選擇 (用於前台顯示樣式)
                    Forms\Components\Select::make('color')
                        ->label('標籤顏色')
                        ->options([
                            'primary' => '藍色 (Primary)',
                            'danger' => '紅色 (Danger)',
                            'success' => '綠色 (Success)',
                            'warning' => '橘色 (Warning)',
                            'gray' => '灰色 (Gray)',
                        ])
                        ->default('primary')
                        ->required(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('名稱')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('代稱'),

                // 用 Badge 顯示顏色，讓後台看起來更直觀
                Tables\Columns\TextColumn::make('color')
                    ->label('顏色預覽')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'primary' => '藍色',
                        'danger' => '紅色',
                        'success' => '綠色',
                        'warning' => '橘色',
                        'gray' => '灰色',
                        default => $state,
                    })
                    ->color(fn(string $state): string => $state),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductTags::route('/'),
            'create' => Pages\CreateProductTag::route('/create'),
            'edit' => Pages\EditProductTag::route('/{record}/edit'),
        ];
    }
}
