<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationGroup = '商店管理';
    protected static ?string $navigationLabel = '商品評價';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('rating')
                    ->label('評分')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5)
                    ->disabled(), // 評分通常不讓管理員改，只能看
                Forms\Components\Textarea::make('comment')
                    ->label('內容')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_visible')
                    ->label('前台顯示'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('商品')
                    ->limit(20)
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('會員')
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating')
                    ->label('評分')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('comment')
                    ->label('內容')
                    ->limit(30),
                Tables\Columns\IconColumn::make('is_visible')
                    ->label('顯示')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('時間')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(), // 允許編輯 (主要是為了隱藏)
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }
}
