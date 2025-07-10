<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewImageResource\Pages;
use App\Models\ReviewImage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReviewImageResource extends Resource
{
    protected static ?string $model = ReviewImage::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'コンテンツ管理';

    protected static ?string $navigationLabel = '画像検閲';

    protected static ?string $modelLabel = 'レビュー画像';

    protected static ?string $pluralModelLabel = 'レビュー画像一覧';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('moderation_status')
                    ->label('検閲ステータス')
                    ->options([
                        'published' => '公開',
                        'under_review' => '検閲中',
                        'rejected' => '拒否',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('moderation_notes')
                    ->label('検閲メモ')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('original_name')
                    ->label('オリジナルファイル名')
                    ->disabled(),
                Forms\Components\TextInput::make('file_size')
                    ->label('ファイルサイズ')
                    ->numeric()
                    ->suffix('bytes')
                    ->disabled(),
                Forms\Components\TextInput::make('mime_type')
                    ->label('MIMEタイプ')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail_path')
                    ->label('サムネイル')
                    ->disk('public')
                    ->height(50),
                Tables\Columns\TextColumn::make('review.user.name')
                    ->label('投稿者')
                    ->sortable(),
                Tables\Columns\TextColumn::make('review.shop.name')
                    ->label('店舗')
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('moderation_status')
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('original_name')
                    ->label('ファイル名')
                    ->limit(30)
                    ->tooltip(fn (ReviewImage $record): string => $record->original_name),
                Tables\Columns\TextColumn::make('file_size')
                    ->label('サイズ')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 1024, 1) . 'KB')
                    ->sortable(),
                Tables\Columns\TextColumn::make('moderator.name')
                    ->label('モデレーター')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('moderated_at')
                    ->label('検閲日時')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
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
            ])
            ->actions([
                Tables\Actions\Action::make('view_image')
                    ->label('画像表示')
                    ->icon('heroicon-o-eye')
                    ->url(fn (ReviewImage $record): string => $record->medium_url)
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('承認')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (ReviewImage $record) {
                        $record->update([
                            'moderation_status' => 'published',
                            'moderated_by' => auth()->id(),
                            'moderated_at' => now(),
                        ]);
                    })
                    ->visible(fn (ReviewImage $record): bool => $record->moderation_status !== 'published'),
                Tables\Actions\Action::make('reject')
                    ->label('拒否')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (ReviewImage $record) {
                        $record->update([
                            'moderation_status' => 'rejected',
                            'moderated_by' => auth()->id(),
                            'moderated_at' => now(),
                        ]);
                    })
                    ->visible(fn (ReviewImage $record): bool => $record->moderation_status !== 'rejected'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve')
                        ->label('一括承認')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'moderation_status' => 'published',
                                    'moderated_by' => auth()->id(),
                                    'moderated_at' => now(),
                                ]);
                            });
                        }),
                    Tables\Actions\BulkAction::make('reject')
                        ->label('一括拒否')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'moderation_status' => 'rejected',
                                    'moderated_by' => auth()->id(),
                                    'moderated_at' => now(),
                                ]);
                            });
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // 30秒ごとに自動更新
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
            'index' => Pages\ListReviewImages::route('/'),
            'create' => Pages\CreateReviewImage::route('/create'),
            'edit' => Pages\EditReviewImage::route('/{record}/edit'),
        ];
    }
}
