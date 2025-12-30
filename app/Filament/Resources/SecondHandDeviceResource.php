<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SecondHandDeviceResource\Pages;
use App\Filament\Resources\SecondHandDeviceResource\RelationManagers;
use App\Models\SecondHandDevice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Grid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SecondHandDeviceResource extends Resource
{
    // 關閉功能 (從選單隱藏)
    // --------------
    protected static ?string $navigationIcon = null;

    public static function shouldRegisterNavigation(): bool
    {
        return false; // 永遠不顯示在選單
    }
    // --------------

    protected static ?string $model = SecondHandDevice::class;

    // protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = '二手機管理'; // 這裡決定左側選單顯示什麼
    protected static ?string $modelLabel = '二手機'; // 這裡決定按鈕顯示 "新增二手機"
    protected static ?string $pluralModelLabel = '二手機'; // 複數形

    protected static ?int $navigationSort = 7;
    // 離開群組，回到最外層

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('商品名稱')
                        ->required(),
                    Forms\Components\TextInput::make('price')
                        ->label('售價')
                        ->numeric()
                        ->prefix('NT$')
                        ->required(),
                ]),
                Forms\Components\TextInput::make('condition')
                    ->label('機況 (例如：外觀無傷、電池健康度 95%)'),

                Forms\Components\FileUpload::make('image')
                    ->label('商品實照')
                    ->image()
                    ->directory('second-hand'),

                Forms\Components\RichEditor::make('specs')
                    ->label('詳細規格/描述')
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_sold')
                    ->label('已售出 (前台將顯示 Sold Out)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('圖片'),
                Tables\Columns\TextColumn::make('name')->label('名稱')->searchable(),
                Tables\Columns\TextColumn::make('price')->money('TWD')->label('售價'),
                Tables\Columns\IconColumn::make('is_sold')
                    ->label('已售出')
                    ->boolean(),
            ])
            ->actions([Tables\Actions\EditAction::make(),]);
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
            'index' => Pages\ListSecondHandDevices::route('/'),
            'create' => Pages\CreateSecondHandDevice::route('/create'),
            'edit' => Pages\EditSecondHandDevice::route('/{record}/edit'),
        ];
    }
}
