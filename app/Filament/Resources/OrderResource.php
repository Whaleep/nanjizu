<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
// use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Support\HtmlString;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = '商店管理';
    protected static ?string $navigationLabel = '訂單管理';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = '訂單';
    protected static ?string $pluralModelLabel = '訂單';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make('訂單資訊')->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->label('訂單編號')
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->label('訂單狀態')
                            ->options([
                                'pending' => '待處理',
                                'processing' => '處理中',
                                'shipped' => '已出貨/待取貨',
                                'completed' => '已完成',
                                'cancelled' => '已取消',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('總金額')
                            ->prefix('NT$')
                            ->disabled(),

                        Forms\Components\Select::make('payment_method')
                            ->label('付款方式')
                            ->options([
                                'cod' => '貨到付款',
                                'bank_transfer' => '銀行匯款',
                            ]),
                    ])->columns(2),

                    Forms\Components\Section::make('購買商品')->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('product_name')->label('商品'),
                                Forms\Components\TextInput::make('variant_name')->label('規格'),
                                Forms\Components\TextInput::make('price')->label('單價'),
                                Forms\Components\TextInput::make('quantity')->label('數量'),
                                Forms\Components\TextInput::make('subtotal')->label('小計'),
                            ])
                            ->columns(5)
                            ->disabled() // 禁止修改明細
                            ->deletable(false)
                            ->addable(false),
                    ]),
                ])->columnSpan(2),

                Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make('客戶資料')->schema([
                        // 新增：會員連結顯示
                        Placeholder::make('user_link')
                            ->label('會員帳號')
                            ->content(
                                fn(Order $record): ?HtmlString => $record->user_id
                                    ? new HtmlString('<a href="' . UserResource::getUrl('edit', ['record' => $record->user_id]) . '" target="_blank" class="text-primary-600 hover:underline font-bold">前往會員資料頁面的捷徑 ↗</a>')
                                    : new HtmlString('<span class="text-gray-500">訪客結帳 (無會員資料)</span>')
                            )
                            ->visibleOn('edit'), // 只在編輯/查看模式顯示，新增模式隱藏

                        Forms\Components\TextInput::make('customer_name')->label('姓名'),
                        Forms\Components\TextInput::make('customer_phone')->label('電話'),
                        Forms\Components\Textarea::make('customer_address')->label('地址'),
                        Forms\Components\TextInput::make('customer_email')->label('Email'),
                        Forms\Components\Textarea::make('notes')->label('備註'),
                    ]),
                ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            // ->query(fn(Builder $query) => $query->with('user'))
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->label('編號')->searchable()->sortable(),
                // 新增：會員連結
                Tables\Columns\TextColumn::make('user.name') // 關聯到 User 的 name
                    ->label('會員帳號')
                    ->placeholder('訪客結帳') // 如果 user_id 是 null 顯示這個
                    ->color('primary') // 設為藍色看起來像連結
                    // ->weight('bold')
                    ->url(
                        fn(Order $record): ?string => $record->user_id
                            ? UserResource::getUrl('edit', ['record' => $record->user_id])
                            : null
                    ) // 設定跳轉網址
                    ->openUrlInNewTab(), // 在新分頁開啟
                Tables\Columns\TextColumn::make('created_at')->label('下單時間')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('customer_name')->label('收件人姓名'),
                Tables\Columns\TextColumn::make('total_amount')->label('金額')->money('TWD')->alignEnd(),
                Tables\Columns\TextColumn::make('status')
                    ->label('狀態')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => '待處理',
                        'processing' => '處理中',
                        'shipped' => '已出貨/待取貨',
                        'completed' => '已完成',
                        'cancelled' => '已取消',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'gray',
                        'processing' => 'warning',
                        'shipped' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),

                // 列印訂單按鈕
                Action::make('print')
                    ->label('列印')
                    ->icon('heroicon-o-printer')
                    ->url(fn(Order $record) => route('admin.orders.print', $record))
                    ->openUrlInNewTab(), // 新分頁開啟
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
            'index' => Pages\ListOrders::route('/'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
