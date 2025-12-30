<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeviceModelResource\Pages;
use App\Filament\Resources\DeviceModelResource\RelationManagers;
use App\Models\DeviceModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;

class DeviceModelResource extends Resource
{
    protected static ?string $model = DeviceModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationLabel = '裝置型號';
    protected static ?string $modelLabel = '型號';
    protected static ?string $pluralModelLabel = '型號';

    protected static ?string $navigationGroup = '維修資料庫';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('brand_id')
                    ->relationship('brand', 'name')
                    ->label('品牌')
                    ->required()
                    ->live(), // 開啟即時更新，以便連動下一個選單

                // === 新增這個欄位 ===
                Forms\Components\Select::make('device_category_id')
                    ->label('產品系列')
                    ->options(function (Forms\Get $get) {
                        $brandId = $get('brand_id');
                        if (!$brandId) {
                            return [];
                        }
                        // 只顯示該品牌底下的系列
                        return \App\Models\DeviceCategory::where('brand_id', $brandId)
                            ->pluck('name', 'id');
                    })
                    ->required(),
                // ==================

                Forms\Components\TextInput::make('name')
                    ->label('型號名稱')
                    ->required(),
                Forms\Components\TextInput::make('slug')
                    ->label('網址代稱')
                    ->placeholder('自動生成'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('brand.name')
                    ->label('品牌')
                    ->sortable(),

                // === 新增：顯示產品系列 ===
                Tables\Columns\TextColumn::make('deviceCategory.name')
                    ->label('系列')
                    ->sortable()
                    ->searchable()
                    ->placeholder('未分類'), // 若沒有系列顯示這個
                // ========================

                Tables\Columns\TextColumn::make('name')
                    ->label('型號')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('排序')
                    ->sortable(),
            ])
            ->defaultSort('sort_order', 'asc') // 預設依照排序欄位
            ->filters([
                // === 新增篩選器 ===
                Tables\Filters\SelectFilter::make('brand_id')
                    ->relationship('brand', 'name')
                    ->label('篩選品牌'),

                Tables\Filters\SelectFilter::make('device_category_id')
                    ->relationship('deviceCategory', 'name')
                    ->label('篩選系列'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // 這裡呼叫管理器，請確保上面的 use 有正確引入
            RelationManagers\PricesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeviceModels::route('/'),
            'create' => Pages\CreateDeviceModel::route('/create'),
            'edit' => Pages\EditDeviceModel::route('/{record}/edit'),
        ];
    }
}
