<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsResource\Pages;
use App\Models\Post; // 這裡指向 Post 模型
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class NewsResource extends Resource
{
    protected static ?string $model = Post::class; // 指定使用 Post 資料表

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationLabel = '最新消息管理';
    protected static ?string $modelLabel = '最新消息';
    protected static ?string $pluralModelLabel = '最新消息';
    protected static ?int $navigationSort = 1;

    // 列表過濾：只顯示最新消息
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('category', 'news');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('標題')
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

                // === 關鍵修改：使用 Select 讓小編可以切換分類 ===
                Forms\Components\Select::make('category')
                    ->label('文章分類')
                    ->options([
                        'news' => '最新消息',
                        'case' => '維修案例',
                    ])
                    ->default('news') // 預設選 news
                    ->required()
                    ->helperText('若修改為「維修案例」，儲存後此文章將移至維修案例列表。'),
                // =============================================

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
                Tables\Columns\SpatieMediaLibraryImageColumn::make('featured_image')
                    ->label('封面')
                    ->collection('featured_image'),
                Tables\Columns\TextColumn::make('title')->label('標題')->searchable(),
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
            'index' => Pages\ListNews::route('/'),
            'create' => Pages\CreateNews::route('/create'),
            'edit' => Pages\EditNews::route('/{record}/edit'),
        ];
    }
}
