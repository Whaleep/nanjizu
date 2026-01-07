<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = '核心系統';
    protected static ?string $navigationLabel = '帳號管理';
    protected static ?string $modelLabel = '帳號';
    protected static ?string $pluralModelLabel = '帳號';
    protected static ?int $navigationSort = 99; // 排在最後面

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('帳號資訊')->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('姓名')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->label('電子信箱 (登入帳號)')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    // === 新增：權限設定 ===
                    Forms\Components\Select::make('role')
                        ->label('身分權限')
                        ->options([
                            'admin' => '管理員 (可登入後台)',
                            'customer' => '一般會員',
                        ])
                        ->default('customer')
                        ->required()
                        ->native(false), // 使用漂亮的 Filament 下拉樣式

                    // 密碼設定 (保持原本邏輯)
                    Forms\Components\TextInput::make('password')
                        ->label('密碼')
                        ->password()
                        ->dehydrateStateUsing(fn($state) => Hash::make($state))
                        ->dehydrated(fn($state) => filled($state))
                        ->required(fn(string $operation): bool => $operation === 'create')
                        ->maxLength(255)
                        ->helperText('若不修改密碼請留空'),
                ])->columns(2),

                // === 新增：聯絡資料 (補上之前 Database 加的欄位) ===
                Forms\Components\Section::make('聯絡資料')->schema([
                    Forms\Components\TextInput::make('phone')
                        ->label('電話號碼')
                        ->tel(),

                    Forms\Components\Textarea::make('address')
                        ->label('地址')
                        ->rows(2)
                        ->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('姓名')
                    ->searchable()
                    ->sortable(),

                // === 新增：身分顯示 (Badge) ===
                Tables\Columns\TextColumn::make('role')
                    ->label('身分')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'admin' => '管理員',
                        'customer' => '會員',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'admin' => 'danger',   // 紅色：管理員
                        'customer' => 'success', // 綠色：會員
                        default => 'gray',
                    })
                    ->sortable(),
                // ===========================

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('電話')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('註冊時間')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // 預設隱藏，需要時可在列表右上角開啟
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // === 新增：身分篩選器 ===
                Tables\Filters\SelectFilter::make('role')
                    ->label('篩選身分')
                    ->options([
                        'admin' => '管理員',
                        'customer' => '一般會員',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
