<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Filament\Blocks\ContentBlocks;
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
    protected static ?string $modelLabel = '自訂頁面';
    protected static ?string $pluralModelLabel = '自訂頁面';

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

                    Forms\Components\Section::make('頁面內容')->schema([
                        Builder::make('content')
                            ->label('頁面區塊')
                            ->blocks(ContentBlocks::make()) // <--- 呼叫共用設定
                            ->collapsible()
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
