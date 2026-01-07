<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders'; // User->orders()
    protected static ?string $title = '會員訂單紀錄';

    public function form(Form $form): Form
    {
        // 這裡通常不需要 Form，因為我們不會在這裡「新增」訂單
        // 但如果需要查看詳情，可以簡單定義一些唯讀欄位
        return $form->schema([
            Forms\Components\TextInput::make('order_number')->disabled(),
            Forms\Components\TextInput::make('total_amount')->disabled(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_number')
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('訂單編號')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('下單日期')
                    ->dateTime(),

                Tables\Columns\TextColumn::make('status')
                    ->label('狀態')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'processing' => 'warning',
                        'shipped' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('金額')
                    ->money('TWD')
                    ->alignEnd(),
            ])
            ->filters([
                // 可以加狀態篩選
            ])
            ->headerActions([
                // 通常不允許後台直接幫客戶下單，所以不加 CreateAction
            ])
            ->actions([
                // 允許點擊跳轉到真正的訂單編輯頁面 (OrderResource)
                Tables\Actions\Action::make('view')
                    ->label('查看詳情')
                    ->url(fn ($record) => \App\Filament\Resources\OrderResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
