<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShopMenuResource\Pages;
use App\Models\ShopMenu;
use App\Models\ShopCategory;
use App\Models\ProductTag;
use App\Models\Promotion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ShopMenuResource extends Resource
{
    protected static ?string $model = ShopMenu::class;
    protected static ?string $navigationIcon = 'heroicon-o-bars-3';
    protected static ?string $navigationGroup = '商店管理';
    protected static ?string $navigationLabel = '商店主選單';
    protected static ?int $navigationSort = 0;
    protected static ?string $modelLabel = '商店主選單';
    protected static ?string $pluralModelLabel = '商店主選單';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('顯示名稱')
                    ->required(),

                Forms\Components\Select::make('type')
                    ->label('連結類型')
                    ->options([
                        'category' => '商品分類',
                        'tag' => '商品標籤 (群組)',
                        'promotion' => '特惠活動',
                        'link' => '自訂連結',
                    ])
                    ->required()
                    ->reactive(), // 變動時觸發介面更新

                // 分類選擇器
                Forms\Components\Select::make('target_id')
                    ->label('選擇分類')
                    ->options(ShopCategory::whereNull('parent_id')->pluck('name', 'id')) // 只選第一層
                    ->searchable()
                    ->hidden(fn(Forms\Get $get) => $get('type') !== 'category')
                    ->required(fn(Forms\Get $get) => $get('type') === 'category'),

                // 標籤選擇器
                Forms\Components\Select::make('target_id')
                    ->label('選擇標籤')
                    ->options(ProductTag::pluck('name', 'id'))
                    ->searchable()
                    ->hidden(fn(Forms\Get $get) => $get('type') !== 'tag')
                    ->required(fn(Forms\Get $get) => $get('type') === 'tag'),

                // 特惠活動選擇器
                Forms\Components\Select::make('target_id')
                    ->label('選擇活動')
                    ->options(Promotion::where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->hidden(fn(Forms\Get $get) => $get('type') !== 'promotion')
                    ->required(fn(Forms\Get $get) => $get('type') === 'promotion')
                    ->helperText('連結將導向至活動專屬商品頁'),

                Forms\Components\TextInput::make('url')
                    ->label('輸入網址')
                    ->hidden(fn(Forms\Get $get) => $get('type') !== 'link')
                    ->required(fn(Forms\Get $get) => $get('type') === 'link'),

                Forms\Components\Hidden::make('sort_order')->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('名稱')->weight('bold'),
                Tables\Columns\TextColumn::make('type')
                    ->label('類型')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'category' => '分類',
                        'tag' => '標籤',
                        'promotion' => '特惠活動',
                        'link' => '連結',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'category' => 'info',
                        'tag' => 'success',
                        'promotion' => 'warning',
                        'link' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->reorderable('sort_order') // 開啟拖曳排序
            ->defaultSort('sort_order', 'asc')
            ->paginated(false)
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
            'index' => Pages\ListShopMenus::route('/'),
            'create' => Pages\CreateShopMenu::route('/create'),
            'edit' => Pages\EditShopMenu::route('/{record}/edit'),
        ];
    }
}
