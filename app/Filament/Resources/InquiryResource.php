<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InquiryResource\Pages;
// use App\Filament\Resources\InquiryResource\RelationManagers;
use App\Models\Inquiry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;

class InquiryResource extends Resource
{
    protected static ?string $model = Inquiry::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = '預約詢問單';
    protected static ?string $modelLabel = '詢問單';
    protected static ?string $pluralModelLabel = '詢問單';

    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('customer_name')->label('客戶姓名')->readOnly(),
                Forms\Components\TextInput::make('phone')->label('電話')->readOnly(),
                Forms\Components\TextInput::make('device_model')->label('裝置')->readOnly(),
                Forms\Components\Textarea::make('message')->label('故障描述')->readOnly(),

                Forms\Components\Select::make('status')
                    ->label('處理狀態')
                    ->options([
                        'pending' => '待處理',
                        'contacted' => '已聯絡',
                        'finished' => '已完修',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('時間')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('customer_name')->label('姓名'),
                Tables\Columns\TextColumn::make('phone')->label('電話'),
                Tables\Columns\TextColumn::make('device_model')->label('裝置'),
                Tables\Columns\TextColumn::make('status')
                    ->label('狀態')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'danger',
                        'contacted' => 'warning',
                        'finished' => 'success',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInquiries::route('/'),
            'edit' => Pages\EditInquiry::route('/{record}/edit'),
        ];
    }
}
