<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RepairCaseResource\Pages;
use App\Models\Post; // 指向 Post
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class RepairCaseResource extends Resource
{
    protected static ?string $model = Post::class; // 指定 Post

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = '維修案例管理';
    protected static ?string $modelLabel = '維修案例';
    protected static ?string $pluralModelLabel = '維修案例';
    protected static ?int $navigationSort = 2;

    // 列表過濾：只顯示維修案例
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('category', 'case');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('案例標題')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(
                            fn(string $operation, $state, Forms\Set $set) =>
                            $operation === 'create' ? $set('slug', Str::slug($state)) : null
                        ),
                    Forms\Components\TextInput::make('slug')
                        ->label('網址代稱')
                        ->required()
                        ->unique(ignoreRecord: true),
                ]),

                // === 這裡預設選 case，但也可以切回 news ===
                Forms\Components\Select::make('category')
                    ->label('文章分類')
                    ->options([
                        'news' => '最新消息',
                        'case' => '維修案例',
                    ])
                    ->default('case') // 預設選 case
                    ->required()
                    ->helperText('若修改為「最新消息」，儲存後此文章將移至最新消息列表。'),
                // =======================================

                Forms\Components\FileUpload::make('image')
                    ->label('對比圖 / 封面')
                    ->image()
                    ->directory('posts'),

                Forms\Components\Builder::make('content')
                    ->label('內容排版')
                    ->blocks(\App\Filament\Blocks\ContentBlocks::make())
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_published')
                    ->label('是否發布')
                    ->default(true),

                Forms\Components\DateTimePicker::make('published_at')
                    ->label('發布時間')
                    ->default(now()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('圖片'),
                Tables\Columns\TextColumn::make('title')->label('案例標題')->searchable(),
                Tables\Columns\IconColumn::make('is_published')->label('發布')->boolean(),
                Tables\Columns\TextColumn::make('published_at')->label('日期')->date(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRepairCases::route('/'),
            'create' => Pages\CreateRepairCase::route('/create'),
            'edit' => Pages\EditRepairCase::route('/{record}/edit'),
        ];
    }
}
