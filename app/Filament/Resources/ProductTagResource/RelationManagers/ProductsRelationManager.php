<?php

namespace App\Filament\Resources\ProductTagResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products'; // 關聯名稱
    protected static ?string $recordTitleAttribute = 'name'; // 搜尋時顯示的欄位
    protected static ?string $title = '關聯商品管理'; // 標題

    public function form(Form $form): Form
    {
        // 這裡定義的是「建立新商品」的表單，
        // 但通常我們只想「連結現有商品」，所以這裡可以留簡化版或保持預設
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('product_images')->label('圖片')
                    ->collection('product_images')->circular()->stacked(),
                Tables\Columns\TextColumn::make('name')->label('商品名稱')->searchable(),
                Tables\Columns\TextColumn::make('category.name')->label('分類'),
                Tables\Columns\IconColumn::make('is_active')->label('上架')->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // 允許「連結」現有商品
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect() // 如果商品不多可開啟預載
                    ->label('加入商品到此標籤'),
            ])
            ->actions([
                // 允許「解除連結」 (不會刪除商品，只會移除標籤關聯)
                Tables\Actions\DetachAction::make()->label('')->tooltip('解除標籤連結')->icon('heroicon-o-backspace'),
            ]);
    }
}
