<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Filament\Resources\CouponResource\RelationManagers;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = '商店管理';
    protected static ?string $navigationLabel = '優惠券管理';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基本設定')->schema([
                    Forms\Components\TextInput::make('code')
                        ->label('優惠碼 (大寫英文數字)')
                        ->required()
                        ->regex('/^[A-Z0-9]+$/')
                        ->validationMessages([
                            'regex' => '優惠碼只能包含大寫英文字母與數字。',
                        ])
                        ->dehydrateStateUsing(fn($state) => strtoupper($state)) // 確保存入資料庫必為大寫
                        ->extraInputAttributes(['style' => 'text-transform: uppercase;']),

                    Forms\Components\Select::make('type')
                        ->label('折扣類型')
                        ->options([
                            'fixed' => '定額折扣 (例如折 $100)',
                            'percent' => '百分比折扣 (例如打 9 折)',
                        ])
                        ->default('fixed')
                        ->required(),

                    Forms\Components\TextInput::make('value')
                        ->label('折扣數值')
                        ->numeric()
                        ->required()
                        ->helperText('若是百分比，輸入 10 代表折 10% (打九折)'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('啟用中')
                        ->default(true),
                ])->columns(2),

                Forms\Components\Section::make('限制條件 (選填)')->schema([
                    Forms\Components\TextInput::make('min_spend')
                        ->label('最低消費金額')
                        ->numeric()
                        ->prefix('$'),

                    Forms\Components\TextInput::make('usage_limit')
                        ->label('總使用次數限制')
                        ->numeric(),

                    Forms\Components\DateTimePicker::make('start_at')->label('開始時間'),
                    Forms\Components\DateTimePicker::make('end_at')->label('結束時間'),
                ])->columns(2),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('優惠碼')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('type')
                    ->label('類型')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'fixed' => '定額',
                        'percent' => '%',
                    }),
                Tables\Columns\TextColumn::make('value')->label('數值'),
                Tables\Columns\TextColumn::make('used_count')->label('已使用'),
                Tables\Columns\TextColumn::make('end_at')->label('到期日')->date()->placeholder('無期限'),
                Tables\Columns\IconColumn::make('is_active')->label('啟用')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
