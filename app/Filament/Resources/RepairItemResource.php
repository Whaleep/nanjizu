<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RepairItemResource\Pages;
// use App\Filament\Resources\RepairItemResource\RelationManagers;
// use App\Filament\Resources\DeviceModelResource\RelationManagers;
use App\Models\RepairItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;

class RepairItemResource extends Resource
{
    protected static ?string $model = RepairItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = '維修項目';
    protected static ?string $modelLabel = '維修';
    protected static ?string $pluralModelLabel = '維修';

    protected static ?string $navigationGroup = '維修資料庫';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('維修項目名稱')
                    ->required(),
                // 新增排序欄位
                Forms\Components\TextInput::make('sort_order')
                    ->label('顯示排序 (越小越前面)')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // 為了讓畫面乾淨，原本顯示 sort_order 數字的欄位其實可以隱藏或拿掉
                // 因為有了拖曳把手，就不太需要看數字了
                // Tables\Columns\TextColumn::make('sort_order')->label('排序'),

                Tables\Columns\TextColumn::make('name')
                    ->label('維修項目名稱')
                    ->weight('bold'), // 加粗一點比較好看
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            // === 關鍵設定 ===
            ->reorderable('sort_order') // 啟用拖曳排序，並指定儲存到 sort_order 欄位
            ->defaultSort('sort_order', 'asc') // 確保列表預設就是照順序排
            ->paginated(false); // 建議關閉分頁，因為要拖曳排序，一次顯示全部比較好操作
    }

    public static function getRelations(): array
    {
        return [
            // 這裡必須是空的！絕對不能放 PricesRelationManager
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRepairItems::route('/'),
            'create' => Pages\CreateRepairItem::route('/create'),
            'edit' => Pages\EditRepairItem::route('/{record}/edit'),
        ];
    }
}
