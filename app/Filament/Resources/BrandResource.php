<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Filament\Resources\BrandResource\RelationManagers;
use App\Models\Brand;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = '品牌管理';
    protected static ?string $modelLabel = '品牌';
    protected static ?string $pluralModelLabel = '品牌';

    protected static ?string $navigationGroup = '維修資料庫'; // 建立群組
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->label('品牌名稱')->required(),
                SpatieMediaLibraryFileUpload::make('brand_logo')
                    ->label('品牌 Logo')
                    ->collection('brand_logo'),
                Forms\Components\TextInput::make('sort_order')->label('排序')->numeric()->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('brand_logo')
                    ->label('Logo')
                    ->collection('brand_logo'),
                Tables\Columns\TextColumn::make('name')->label('品牌名稱')->sortable(),
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
            'index' => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'edit' => Pages\EditBrand::route('/{record}/edit'),
        ];
    }
}
