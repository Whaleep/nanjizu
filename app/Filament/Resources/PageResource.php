<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Filament\Resources\PageResource\RelationManagers;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Builder; // 引入 Builder
use Illuminate\Support\Str;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?string $navigationGroup = '內容管理';
    protected static ?string $navigationLabel = '自訂頁面';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()->schema([
                    // 基本設定
                    Forms\Components\Section::make('頁面設定')->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('頁面標題')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Forms\Set $set, ?string $state) =>
                            $set('slug', Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->label('網址 slug (例如: about)')
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\Toggle::make('is_published')
                            ->label('發布')
                            ->default(true),
                    ])->columns(2),

                    // === 核心：視覺化編輯器 ===
                    Forms\Components\Section::make('頁面內容')->schema([
                        Builder::make('content')
                            ->label('頁面區塊')
                            ->blocks([
                                // 區塊 1: Hero Banner
                                Builder\Block::make('hero')
                                    ->label('英雄看板 (Hero)')
                                    ->schema([
                                        Forms\Components\TextInput::make('heading')->label('主標題')->required(),
                                        Forms\Components\TextInput::make('subheading')->label('副標題'),
                                        Forms\Components\FileUpload::make('image')
                                            ->label('背景圖片')
                                            ->image()
                                            ->directory('pages'),
                                        Forms\Components\TextInput::make('button_text')->label('按鈕文字'),
                                        Forms\Components\TextInput::make('button_url')->label('按鈕連結'),
                                    ]),

                                // 區塊 2: 富文本 (Rich Text)
                                Builder\Block::make('text_content')
                                    ->label('純文字內容')
                                    ->schema([
                                        Forms\Components\RichEditor::make('body')
                                            ->label('內容')
                                            ->required(),
                                    ]),

                                // 區塊 3: 圖文並茂 (Image + Text)
                                Builder\Block::make('image_with_text')
                                    ->label('圖文區塊')
                                    ->schema([
                                        Forms\Components\Select::make('layout')
                                            ->label('排版方向')
                                            ->options([
                                                'left' => '圖在左，文在右',
                                                'right' => '圖在右，文在左',
                                            ])
                                            ->default('left'),
                                        Forms\Components\FileUpload::make('image')
                                            ->label('圖片')
                                            ->image()
                                            ->directory('pages'),
                                        Forms\Components\RichEditor::make('content')
                                            ->label('文字內容'),
                                    ]),
                            ])
                            ->collapsible() // 可折疊
                            ->cloneable(), // 可複製區塊
                    ]),
                ])->columnSpanFull(),
            ]);
    }


public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('標題')->searchable(),
                Tables\Columns\TextColumn::make('slug')->label('網址'),
                Tables\Columns\IconColumn::make('is_published')->label('狀態')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->label('更新時間')->date(),
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
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
