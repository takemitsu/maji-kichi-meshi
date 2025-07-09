<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RankingResource\Pages;
use App\Filament\Resources\RankingResource\RelationManagers;
use App\Models\Ranking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

class RankingResource extends Resource
{
    protected static ?string $model = Ranking::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    
    protected static ?string $navigationGroup = 'コンテンツ管理';
    
    protected static ?string $navigationLabel = 'ランキング管理';
    
    protected static ?string $modelLabel = 'ランキング';
    
    protected static ?string $pluralModelLabel = 'ランキング一覧';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('作成者')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->disabled(),
                Forms\Components\Select::make('shop_id')
                    ->label('店舗')
                    ->relationship('shop', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('category_id')
                    ->label('カテゴリ')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('rank_position')
                    ->label('順位')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(100)
                    ->required(),
                Forms\Components\Toggle::make('is_public')
                    ->label('公開設定')
                    ->default(true)
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->label('タイトル')
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('説明')
                    ->columnSpanFull()
                    ->rows(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('作成者')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('カテゴリ')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('shop.name')
                    ->label('店舗')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('rank_position')
                    ->label('順位')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 3 => 'success',
                        $state <= 10 => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (int $state): string => $state . '位')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('タイトル')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn (Ranking $record): ?string => $record->title),
                Tables\Columns\TextColumn::make('description')
                    ->label('説明')
                    ->limit(40)
                    ->tooltip(fn (Ranking $record): ?string => $record->description),
                Tables\Columns\IconColumn::make('is_public')
                    ->label('公開')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('作成日')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('更新日')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('カテゴリ')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('is_public')
                    ->label('公開設定')
                    ->options([
                        '1' => '公開',
                        '0' => '非公開',
                    ]),
                Filter::make('top_rankings')
                    ->label('上位ランキング（10位以内）')
                    ->query(fn (Builder $query): Builder => $query->where('rank_position', '<=', 10)),
                Filter::make('recent')
                    ->label('最近の作成（7日以内）')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(7))),
                Filter::make('private_rankings')
                    ->label('非公開ランキング')
                    ->query(fn (Builder $query): Builder => $query->where('is_public', false)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_public')
                    ->label(fn (Ranking $record): string => $record->is_public ? '非公開にする' : '公開にする')
                    ->icon(fn (Ranking $record): string => $record->is_public ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (Ranking $record): string => $record->is_public ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (Ranking $record) => $record->update(['is_public' => !$record->is_public])),
                Tables\Actions\DeleteAction::make()
                    ->label('削除')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('make_public')
                        ->label('一括公開')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['is_public' => true]))),
                    Tables\Actions\BulkAction::make('make_private')
                        ->label('一括非公開')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['is_public' => false]))),
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
            'index' => Pages\ListRankings::route('/'),
            'create' => Pages\CreateRanking::route('/create'),
            'edit' => Pages\EditRanking::route('/{record}/edit'),
        ];
    }
}
