<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeviceCategoryResource\Pages;
use App\Filament\Resources\DeviceCategoryResource\RelationManagers;
use App\Models\DeviceCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DeviceCategoryResource extends Resource
{
    protected static ?string $model = DeviceCategory::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = '產品系列設定';
    protected static ?string $modelLabel = '系列';
    protected static ?string $pluralModelLabel = '系列';

    protected static ?string $navigationGroup = '維修資料庫';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('brand_id')
                    ->relationship('brand', 'name')
                    ->label('所屬品牌')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('系列名稱 (如：iPhone 系列)')
                    ->required(),
                Forms\Components\TextInput::make('sort_order')
                    ->label('排序')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('brand.name')->label('品牌')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('系列名稱'),
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
            'index' => Pages\ListDeviceCategories::route('/'),
            'create' => Pages\CreateDeviceCategory::route('/create'),
            'edit' => Pages\EditDeviceCategory::route('/{record}/edit'),
        ];
    }
}
