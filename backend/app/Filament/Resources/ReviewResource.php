<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'コンテンツ管理';

    protected static ?string $navigationLabel = 'レビュー管理';

    protected static ?string $modelLabel = 'レビュー';

    protected static ?string $pluralModelLabel = 'レビュー一覧';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('投稿者')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->disabled(),
                Forms\Components\Select::make('shop_id')
                    ->label('店舗')
                    ->relationship('shop', 'name')
                    ->searchable()
                    ->preload()
                    ->disabled(),
                Forms\Components\TextInput::make('rating')
                    ->label('評価')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5)
                    ->required(),
                Forms\Components\Select::make('repeat_intention')
                    ->label('リピート意向')
                    ->options([
                        'また行く' => 'また行く',
                        'わからん' => 'わからん',
                        '行かない' => '行かない',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('memo')
                    ->label('メモ')
                    ->columnSpanFull()
                    ->rows(4),
                Forms\Components\DatePicker::make('visited_at')
                    ->label('訪問日')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('投稿者')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('shop.name')
                    ->label('店舗')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('rating')
                    ->label('評価')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (int $state): string => $state . '★')
                    ->sortable(),
                Tables\Columns\TextColumn::make('repeat_intention')
                    ->label('リピート意向')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'また行く' => 'success',
                        'わからん' => 'warning',
                        '行かない' => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('memo')
                    ->label('メモ')
                    ->limit(50)
                    ->tooltip(fn (Review $record): ?string => $record->memo)
                    ->searchable(),
                Tables\Columns\TextColumn::make('images_count')
                    ->label('画像数')
                    ->counts('images')
                    ->sortable(),
                Tables\Columns\TextColumn::make('visited_at')
                    ->label('訪問日')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('投稿日')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('更新日')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('rating')
                    ->label('評価')
                    ->options([
                        '5' => '5★',
                        '4' => '4★',
                        '3' => '3★',
                        '2' => '2★',
                        '1' => '1★',
                    ]),
                SelectFilter::make('repeat_intention')
                    ->label('リピート意向')
                    ->options([
                        'また行く' => 'また行く',
                        'わからん' => 'わからん',
                        '行かない' => '行かない',
                    ]),
                Filter::make('has_images')
                    ->label('画像有り')
                    ->query(fn (Builder $query): Builder => $query->has('images')),
                Filter::make('no_images')
                    ->label('画像無し')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('images')),
                Filter::make('recent')
                    ->label('最近の投稿（7日以内）')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(7))),
                Filter::make('low_rating')
                    ->label('低評価（2★以下）')
                    ->query(fn (Builder $query): Builder => $query->where('rating', '<=', 2)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view_images')
                    ->label('画像確認')
                    ->icon('heroicon-o-photo')
                    ->color('info')
                    ->url(fn (Review $record): string => route('filament.admin.resources.review-images.index', ['tableFilters[review_id][value]' => $record->id]))
                    ->visible(fn (Review $record): bool => $record->images()->count() > 0),
                Tables\Actions\DeleteAction::make()
                    ->label('削除')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('一括削除')
                        ->requiresConfirmation(),
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
            'index' => Pages\ListReviews::route('/'),
            'create' => Pages\CreateReview::route('/create'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }
}
