<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShopImageResource\Pages;
use App\Models\ShopImage;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ShopImageResource extends Resource
{
    protected static ?string $model = ShopImage::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = '店舗画像検閲';

    protected static ?string $modelLabel = '店舗画像';

    protected static ?string $pluralModelLabel = '店舗画像検閲';

    protected static ?string $navigationGroup = 'コンテンツ管理';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('moderation_status')
                    ->label('検閲ステータス')
                    ->options([
                        'published' => '公開',
                        'under_review' => '検閲中',
                        'rejected' => '拒否',
                    ])
                    ->required(),
                \Filament\Forms\Components\Textarea::make('moderation_notes')
                    ->label('検閲メモ')
                    ->columnSpanFull(),
                TextInput::make('original_name')
                    ->label('オリジナルファイル名')
                    ->disabled(),
                TextInput::make('file_size')
                    ->label('ファイルサイズ')
                    ->numeric()
                    ->suffix('bytes')
                    ->disabled(),
                TextInput::make('mime_type')
                    ->label('MIMEタイプ')
                    ->disabled(),
                TextInput::make('sort_order')
                    ->label('並び順')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail_path')
                    ->label('サムネイル')
                    ->disk('public')
                    ->height(50)
                    ->grow(false),

                TextColumn::make('shop.name')
                    ->label('店舗名')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('moderation_status')
                    ->label('検閲ステータス')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'under_review' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'published' => '公開',
                        'under_review' => '検閲中',
                        'rejected' => '拒否',
                        default => '不明',
                    })
                    ->sortable()
                    ->width('1%'),

                TextColumn::make('sort_order')
                    ->label('並び順')
                    ->sortable()
                    ->width('1%'),

                TextColumn::make('moderator.name')
                    ->label('モデレーター')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('moderated_at')
                    ->label('検閲日時')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('投稿日時')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('moderation_status')
                    ->label('検閲ステータス')
                    ->options([
                        'published' => '公開',
                        'under_review' => '検閲中',
                        'rejected' => '拒否',
                    ]),
                SelectFilter::make('mime_type')
                    ->label('ファイル形式')
                    ->options([
                        'image/jpeg' => 'JPEG',
                        'image/png' => 'PNG',
                        'image/gif' => 'GIF',
                        'image/webp' => 'WebP',
                    ]),
                SelectFilter::make('shop_id')
                    ->label('店舗')
                    ->relationship('shop', 'name')
                    ->searchable(),
            ])
            ->actions([
                Action::make('view_image')
                    ->label('画像表示')
                    ->icon('heroicon-o-eye')
                    ->url(fn (ShopImage $record): string => $record->getAttribute('urls')['medium'])
                    ->openUrlInNewTab(),
                EditAction::make(),
                Action::make('approve')
                    ->label('承認')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (ShopImage $record) {
                        $record->approve(auth()->id());
                    })
                    ->visible(fn (ShopImage $record): bool => $record->moderation_status !== 'published'),
                Action::make('reject')
                    ->label('拒否')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (ShopImage $record) {
                        $record->reject(auth()->id());
                    })
                    ->visible(fn (ShopImage $record): bool => $record->moderation_status !== 'rejected'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    \Filament\Tables\Actions\BulkAction::make('approve')
                        ->label('一括承認')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->approve(auth()->id());
                            });
                        }),
                    \Filament\Tables\Actions\BulkAction::make('reject')
                        ->label('一括拒否')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->reject(auth()->id());
                            });
                        }),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
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
            'index' => Pages\ListShopImages::route('/'),
            'create' => Pages\CreateShopImage::route('/create'),
            'edit' => Pages\EditShopImage::route('/{record}/edit'),
        ];
    }
}
