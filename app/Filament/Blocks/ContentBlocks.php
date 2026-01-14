<?php

namespace App\Filament\Blocks;

use Filament\Forms;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use FilamentTiptapEditor\TiptapEditor;

class ContentBlocks
{
    public static function make(): array
    {
        return [
            Block::make('hero')
                ->label('英雄看板 (Hero)')
                ->schema([
                    TextInput::make('heading')->label('標題'),
                    TextInput::make('subheading')->label('副標題'),
                    FileUpload::make('image')->image()->directory('content/hero'),
                    TextInput::make('button_text')->label('按鈕文字'),
                    TextInput::make('button_url')->label('按鈕連結'),
                    Select::make('align')
                        ->label('對齊方式')
                        ->options([
                            'left' => '居左',
                            'center' => '居中',
                            'right' => '居右',
                        ])
                        ->default('center'),
                    TextInput::make('overlay_opacity')
                        ->label('遮罩透明度 (0-100)')
                        ->numeric()
                        ->default(50),
                ]),

            // [英雄輪播 (Hero Carousel)]
            Block::make('hero_carousel')
                ->label('英雄輪播 (Carousel)')
                ->schema([
                    Repeater::make('slides')
                        ->label('投影片列表')
                        ->schema([
                            FileUpload::make('image')->label('背景圖')->image()->directory('content/carousel')->required(),
                            TextInput::make('heading')->label('主標題'),
                            TextInput::make('subheading')->label('副標題'),
                            TextInput::make('button_text')->label('按鈕文字'),
                            TextInput::make('button_url')->label('按鈕連結'),
                        ])
                        ->collapsible()
                        ->itemLabel(fn(array $state): ?string => $state['heading'] ?? '投影片')
                        ->addActionLabel('新增投影片'),
                    TextInput::make('autoplay_delay')
                        ->label('自動播放延遲 (毫秒)')
                        ->numeric()
                        ->default(5000),
                ]),

            // [純文字/HTML]
            Block::make('text_content')
                ->label('純文字/HTML')
                ->schema([
                    TiptapEditor::make('body') // 注意：前端要用 item.body
                        ->label('內容')
                        ->profile('default'),
                ]),

            // [圖文並茂]
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

            // [收合面板 (Accordion)]
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

            // [規格表 (Specification)]
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

            // [彈窗按鈕 (Modal)]
            Block::make('modal_btn')
                ->label('彈窗按鈕')
                ->schema([
                    TextInput::make('btn_text')->required(),
                    TextInput::make('modal_title'),
                    TiptapEditor::make('modal_content')->profile('default'),
                ]),

            // [商品列表區塊]
            Block::make('product_grid')
                ->label('商品列表展示')
                ->schema([
                    TextInput::make('heading')->label('區塊標題')->default('最新上架'),
                    TextInput::make('limit')->label('顯示數量')->numeric()->default(8),
                    Select::make('category_id') // 讓老闆可以選特定分類
                        ->label('指定分類 (留空則顯示全部)')
                        ->options(\App\Models\ShopCategory::pluck('name', 'id'))
                        ->searchable(),
                    Select::make('tag_id') // 或選特定標籤
                        ->label('指定標籤 (留空則不篩選)')
                        ->options(\App\Models\ProductTag::pluck('name', 'id'))
                        ->searchable(),
                    Toggle::make('show_cart')
                        ->label('顯示購物車按鈕')
                        ->default(true),
                ]),

            // [文章列表區塊]
            Block::make('post_grid')
                ->label('文章列表 (最新消息/案例)')
                ->schema([
                    TextInput::make('heading')
                        ->label('區塊標題')
                        ->default('最新消息')
                        ->required(),

                    Select::make('type')
                        ->label('文章類型')
                        ->options([
                            'news' => '最新消息',
                            'case' => '維修案例',
                            'all'  => '全部 (混合顯示)',
                        ])
                        ->default('news')
                        ->required(),

                    TextInput::make('limit')
                        ->label('顯示數量')
                        ->numeric()
                        ->default(3)
                        ->helperText('建議輸入 3 的倍數 (每行 3 則)'),

                    Select::make('bg_color')
                        ->label('背景顏色')
                        ->options([
                            'white' => '白色',
                            'gray' => '淺灰 (Gray-100)',
                        ])
                        ->default('white'),
                ]),

            // [快速入口 (Icon Links)]
            Block::make('icon_links')
                ->label('快速入口 (Icon Links)')
                ->schema([
                    Select::make('columns')
                        ->label('顯示欄數')
                        ->options([
                            2 => '2 欄',
                            3 => '3 欄',
                            4 => '4 欄',
                        ])
                        ->default(4),
                    Repeater::make('items')
                        ->label('項目列表')
                        ->schema([
                            TextInput::make('label')->label('文字標籤')->required(),
                            TextInput::make('url')->label('連結路徑')->required(),
                            TextInput::make('icon')->label('圖示 (Emoji)')->required(),
                            Select::make('color')
                                ->label('顏色主題')
                                ->options([
                                    'blue' => '藍色',
                                    'green' => '綠色',
                                    'purple' => '紫色',
                                    'orange' => '橘色',
                                    'red' => '紅色',
                                    'gray' => '灰色',
                                ])
                                ->default('blue'),
                        ])
                        ->columns(2)
                        ->itemLabel(fn(array $state): ?string => $state['label'] ?? '項目連結')
                        ->addActionLabel('新增連結項目'),
                ]),

            // [形象牆 (Feature Wall)]
            Block::make('feature_wall')
                ->label('形象牆 (Special Wall)')
                ->schema([
                    TextInput::make('heading')->label('區塊大標題'),
                    TextInput::make('subheading')->label('區塊副標題'),
                    Repeater::make('items')
                        ->label('內容區塊')
                        ->schema([
                            FileUpload::make('image')->image()->directory('content/wall')->required(),
                            TextInput::make('title')->label('與標題')->required(),
                            TextInput::make('description')->label('詳情描述'),
                            TextInput::make('url')->label('點擊連結'),
                            Select::make('cols')
                                ->label('寬度權重 (1-2 欄)')
                                ->options([
                                    1 => '1 欄寬度',
                                    2 => '2 欄寬度',
                                ])
                                ->default(1),
                            Select::make('rows')
                                ->label('高度權重 (1-2 列)')
                                ->options([
                                    1 => '1 列高度',
                                    2 => '2 列高度',
                                ])
                                ->default(1),
                        ])
                        ->columns(2)
                        ->itemLabel(fn(array $state): ?string => $state['title'] ?? '內容項目')
                        ->addActionLabel('新增牆面內容'),
                ]),

            // [影片區塊]
            Block::make('video')
                ->label('影片 (YouTube / Facebook)')
                ->schema([
                    Forms\Components\Textarea::make('video_url')
                        ->label('影片網址 或 內嵌代碼')
                        ->placeholder('請貼入 YouTube/Facebook 網址，或者 <iframe> 內嵌代碼')
                        ->required()
                        ->rows(2)
                        ->helperText('支援 YouTube (一般/Shorts)、Facebook (一般/Reels)、及直接貼入 <iframe> 代碼'),

                    TextInput::make('heading')
                        ->label('區塊標題 (選填)'),
                ]),
        ];
    }
}
