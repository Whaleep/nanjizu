<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingMethodResource\Pages;
// use App\Filament\Resources\ShippingMethodResource\RelationManagers;
use App\Models\ShippingMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShippingMethodResource extends Resource
{
    protected static ?string $model = ShippingMethod::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = '商店管理';
    protected static ?string $navigationLabel = '運費物流設定';
    protected static ?string $modelLabel = '運費物流';
    protected static ?string $pluralModelLabel = '運費物流';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('運送方式名稱')
                    ->placeholder('例如：黑貓宅配')
                    ->required(),

                Forms\Components\TextInput::make('fee')
                    ->label('運費')
                    ->numeric()
                    ->prefix('$')
                    ->required(),

                Forms\Components\TextInput::make('free_shipping_threshold')
                    ->label('免運門檻')
                    ->numeric()
                    ->prefix('$')
                    ->placeholder('無免運優惠')
                    ->helperText('訂單金額達到此數值即免運，留空則無免運優惠'),

                Forms\Components\TextInput::make('sort_order')
                    ->label('排序')
                    ->numeric()
                    ->default(0),

                Forms\Components\Toggle::make('is_active')
                    ->label('啟用')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('名稱')->sortable(),
                Tables\Columns\TextColumn::make('fee')->label('運費')->money('TWD'),
                Tables\Columns\TextColumn::make('free_shipping_threshold')
                    ->label('免運門檻')
                    ->money('TWD')
                    ->placeholder('無'),
                Tables\Columns\IconColumn::make('is_active')->label('啟用')->boolean(),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order', 'asc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->filters([
                //
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
            'index' => Pages\ListShippingMethods::route('/'),
            'create' => Pages\CreateShippingMethod::route('/create'),
            'edit' => Pages\EditShippingMethod::route('/{record}/edit'),
        ];
    }
}
