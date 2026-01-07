<?php

namespace App\Filament\Blocks;

use Filament\Forms;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use FilamentTiptapEditor\TiptapEditor;

class ContentBlocks
{
    public static function make(): array
    {
        return [
            // 1. 英雄看板
            Block::make('hero')
                ->label('英雄看板 (Hero)')
                ->schema([
                    TextInput::make('heading')->label('標題'),
                    TextInput::make('subheading')->label('副標題'),
                    FileUpload::make('image')->image()->directory('content/hero'),
                    TextInput::make('button_text')->label('按鈕文字'),
                    TextInput::make('button_url')->label('按鈕連結'),
                ]),

            // 2. 純文字/HTML (使用 Tiptap)
            Block::make('text_content')
                ->label('純文字/HTML')
                ->schema([
                    TiptapEditor::make('body') // 注意：前端要用 item.body
                        ->label('內容')
                        ->profile('default'),
                ]),

            // 3. 圖文並茂
            Block::make('image_with_text')
                ->label('圖文區塊')
                ->schema([
                    Select::make('layout')
                        ->options([
                            'left' => '圖在左，文在右',
                            'right' => '圖在右，文在左',
                        ])
                        ->default('left'),
                    FileUpload::make('image')->image()->directory('content/image-text'),
                    TiptapEditor::make('content')->label('文字內容'), // 前端用 item.content
                ]),

            // 4. 收合面板 (Accordion)
            Block::make('accordion')
                ->label('收合說明 (Accordion)')
                ->schema([
                    Repeater::make('items')
                        ->label('項目列表')
                        ->schema([
                            TextInput::make('title')->label('標題')->required(),
                            // 預設展開開關
                            Forms\Components\Toggle::make('is_open')
                                ->label('預設展開')
                                ->default(false), // 預設關閉
                            // 修正：統一用 Tiptap，且欄位名稱為 body 以配合您的前端修正，或是保留 content
                            // 這裡我們統一用 'body' 以防混淆，或者您前端要對應改
                            TiptapEditor::make('body')->label('內容')->required(),
                        ])
                        ->collapsible(),
                ]),

            // 5. 規格表 (Specification)
            Block::make('specification')
                ->label('規格表 (Key-Value)')
                ->schema([
                    TextInput::make('heading')->label('表格標題'),
                    Repeater::make('items')
                        ->schema([
                            TextInput::make('label')->label('項目')->required(),
                            TextInput::make('value')->label('內容')->required(),
                        ])->columns(2),
                ]),

            // 6. 彈窗按鈕 (Modal)
            Block::make('modal_btn')
                ->label('彈窗按鈕')
                ->schema([
                    TextInput::make('btn_text')->required(),
                    TextInput::make('modal_title'),
                    TiptapEditor::make('modal_content')->profile('default'),
                ]),
        ];
    }
}
