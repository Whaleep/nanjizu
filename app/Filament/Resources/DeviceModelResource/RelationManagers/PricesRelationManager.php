<?php

namespace App\Filament\Resources\DeviceModelResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PricesRelationManager extends RelationManager
{
    protected static string $relationship = 'prices'; // 對應 DeviceModel 的 prices() 關聯

    protected static ?string $title = '維修價格表';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('repair_item_id')
                    ->relationship('repairItem', 'name') // 選擇維修項目
                    ->label('維修項目')
                    ->required()
                    ->preload()
                    ->createOptionForm([ // 允許直接在這裡新增新的維修項目名稱
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->label('項目名稱'),
                    ]),

                Forms\Components\TextInput::make('price')
                    ->label('價格')
                    ->numeric()
                    ->prefix('NT$')
                    ->required(),

                Forms\Components\TextInput::make('note')
                    ->label('備註 (如：需留機)')
                    ->nullable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('repairItem.name')->label('維修項目'),
                Tables\Columns\TextColumn::make('price')->money('TWD')->label('價格'),
                Tables\Columns\TextColumn::make('note')->label('備註'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('新增價格'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
?>

