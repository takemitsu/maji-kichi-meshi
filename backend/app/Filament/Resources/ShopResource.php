<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShopResource\Pages;
use App\Models\Shop;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShopResource extends Resource
{
    protected static ?string $model = Shop::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'コンテンツ管理';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('店舗名')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('説明')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('address')
                    ->label('住所')
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('電話番号')
                    ->tel()
                    ->maxLength(20),
                Forms\Components\TextInput::make('website')
                    ->label('ウェブサイト')
                    ->url()
                    ->maxLength(255),
                Forms\Components\Select::make('status')
                    ->label('ステータス')
                    ->options([
                        'active' => 'アクティブ',
                        'hidden' => '非表示',
                        'deleted' => '削除',
                    ])
                    ->default('active')
                    ->required(),
                Forms\Components\Toggle::make('is_closed')
                    ->label('閉店')
                    ->default(false),
                Forms\Components\TextInput::make('latitude')
                    ->label('緯度')
                    ->numeric()
                    ->step(0.0000001),
                Forms\Components\TextInput::make('longitude')
                    ->label('経度')
                    ->numeric()
                    ->step(0.0000001),
                Forms\Components\TextInput::make('google_place_id')
                    ->label('Google Place ID')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('店舗名')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('images_count')
                    ->label('画像数')
                    ->counts('images')
                    ->sortable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('住所')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('status')
                    ->label('ステータス')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'hidden' => 'warning',
                        'deleted' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'アクティブ',
                        'hidden' => '非表示',
                        'deleted' => '削除',
                        default => '不明',
                    })
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_closed')
                    ->label('閉店')
                    ->boolean(),
                Tables\Columns\TextColumn::make('reviews_count')
                    ->label('レビュー数')
                    ->counts('reviews')
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('電話番号')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('website')
                    ->label('ウェブサイト')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('moderator.name')
                    ->label('モデレーター')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('moderated_at')
                    ->label('モデレート日')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('作成日')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('ステータス')
                    ->options([
                        'active' => 'アクティブ',
                        'hidden' => '非表示',
                        'deleted' => '削除',
                    ]),
                Filter::make('is_closed')
                    ->label('閉店のみ')
                    ->query(fn (Builder $query): Builder => $query->where('is_closed', true)),
                Filter::make('has_reviews')
                    ->label('レビュー有り')
                    ->query(fn (Builder $query): Builder => $query->has('reviews')),
                Filter::make('no_reviews')
                    ->label('レビュー無し')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('reviews')),
                Filter::make('has_images')
                    ->label('画像有り')
                    ->query(fn (Builder $query): Builder => $query->has('images')),
                Filter::make('no_images')
                    ->label('画像無し')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('images')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('hide')
                    ->label('非表示')
                    ->icon('heroicon-o-eye-slash')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Shop $record) {
                        $record->update([
                            'status' => 'hidden',
                            'moderated_by' => auth()->id(),
                            'moderated_at' => now(),
                        ]);
                    })
                    ->visible(fn (Shop $record): bool => $record->status === 'active'),
                Tables\Actions\Action::make('show')
                    ->label('表示')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->action(function (Shop $record) {
                        $record->update([
                            'status' => 'active',
                            'moderated_by' => auth()->id(),
                            'moderated_at' => now(),
                        ]);
                    })
                    ->visible(fn (Shop $record): bool => $record->status === 'hidden'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('hide')
                        ->label('一括非表示')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'status' => 'hidden',
                                    'moderated_by' => auth()->id(),
                                    'moderated_at' => now(),
                                ]);
                            });
                        }),
                    Tables\Actions\BulkAction::make('show')
                        ->label('一括表示')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'status' => 'active',
                                    'moderated_by' => auth()->id(),
                                    'moderated_at' => now(),
                                ]);
                            });
                        }),
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
            'index' => Pages\ListShops::route('/'),
            'create' => Pages\CreateShop::route('/create'),
            'edit' => Pages\EditShop::route('/{record}/edit'),
        ];
    }
}
