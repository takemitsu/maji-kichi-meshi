<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'ユーザー管理';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('role')
                    ->options([
                        'user' => 'ユーザー',
                        'admin' => '管理者',
                        'moderator' => 'モデレーター',
                    ])
                    ->default('user')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'アクティブ',
                        'banned' => '停止',
                        'deleted' => '削除',
                    ])
                    ->default('active')
                    ->required(),
                Forms\Components\DateTimePicker::make('email_verified_at'),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('名前')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('メールアドレス')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('権限')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'moderator' => 'warning',
                        'user' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin' => '管理者',
                        'moderator' => 'モデレーター',
                        'user' => 'ユーザー',
                        default => '不明',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('ステータス')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'banned' => 'danger',
                        'deleted' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'アクティブ',
                        'banned' => '停止',
                        'deleted' => '削除',
                        default => '不明',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('reviews_count')
                    ->label('レビュー数')
                    ->counts('reviews')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('登録日')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('メール認証')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('更新日')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('権限')
                    ->options([
                        'user' => 'ユーザー',
                        'admin' => '管理者',
                        'moderator' => 'モデレーター',
                    ]),
                SelectFilter::make('status')
                    ->label('ステータス')
                    ->options([
                        'active' => 'アクティブ',
                        'banned' => '停止',
                        'deleted' => '削除',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('ban')
                    ->label('停止')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (User $record) => $record->update(['status' => 'banned']))
                    ->visible(fn (User $record): bool => $record->status === 'active'),
                Tables\Actions\Action::make('activate')
                    ->label('復活')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn (User $record) => $record->update(['status' => 'active']))
                    ->visible(fn (User $record): bool => $record->status === 'banned'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('ban')
                        ->label('一括停止')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['status' => 'banned']))),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('一括復活')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['status' => 'active']))),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
