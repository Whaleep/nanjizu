<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoreResource\Pages;
use App\Models\Store;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = '分店管理';
    protected static ?string $modelLabel = '分店';
    protected static ?string $pluralModelLabel = '分店';

    protected static ?int $navigationSort = 9;
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('分店名稱')
                    ->required(),
                Forms\Components\TextInput::make('phone')
                    ->label('聯絡電話')
                    ->tel()
                    ->required(),
                Forms\Components\TextInput::make('address')
                    ->label('地址')
                    ->required(),
                Forms\Components\TextInput::make('opening_hours')
                    ->label('營業時間')
                    ->placeholder('週一至週日 11:00 - 21:00')
                    ->required(),
                Forms\Components\Textarea::make('map_url')
                    ->label('Google Maps Embed URL')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->label('啟用中')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('分店'),
                Tables\Columns\TextColumn::make('phone')->label('電話'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('狀態')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListStores::route('/'),
            'create' => Pages\CreateStore::route('/create'),
            'edit' => Pages\EditStore::route('/{record}/edit'),
        ];
    }
}
