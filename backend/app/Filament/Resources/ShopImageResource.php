<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShopImageResource\Pages;
use App\Models\ShopImage;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ShopImageResource extends Resource
{
    protected static ?string $model = ShopImage::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = '店舗画像';

    protected static ?string $modelLabel = '店舗画像';

    protected static ?string $pluralModelLabel = '店舗画像';

    protected static ?string $navigationGroup = 'コンテンツ管理';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Hidden::make('id'),

                Select::make('shop_id')
                    ->relationship('shop', 'name')
                    ->searchable()
                    ->required()
                    ->disabled(),

                TextInput::make('original_name')
                    ->label('元のファイル名')
                    ->disabled(),

                TextInput::make('mime_type')
                    ->label('MIMEタイプ')
                    ->disabled(),

                TextInput::make('file_size')
                    ->label('ファイルサイズ（バイト）')
                    ->disabled(),

                Select::make('status')
                    ->label('ステータス')
                    ->options([
                        'published' => '公開',
                        'under_review' => '審査中',
                        'rejected' => '却下',
                    ])
                    ->required(),

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
                ImageColumn::make('thumbnail_url')
                    ->label('サムネイル')
                    ->circular(),

                TextColumn::make('shop.name')
                    ->label('店舗名')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('original_name')
                    ->label('ファイル名')
                    ->searchable()
                    ->limit(30),

                BadgeColumn::make('status')
                    ->label('ステータス')
                    ->colors([
                        'success' => 'published',
                        'warning' => 'under_review',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'published' => '公開',
                            'under_review' => '審査中',
                            'rejected' => '却下',
                            default => $state,
                        };
                    }),

                TextColumn::make('sort_order')
                    ->label('並び順')
                    ->sortable(),

                TextColumn::make('file_size')
                    ->label('サイズ')
                    ->formatStateUsing(function ($state) {
                        return $state ? number_format($state / 1024, 2) . ' KB' : '';
                    }),

                TextColumn::make('created_at')
                    ->label('作成日')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('moderated_at')
                    ->label('審査日')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('ステータス')
                    ->options([
                        'published' => '公開',
                        'under_review' => '審査中',
                        'rejected' => '却下',
                    ]),

                SelectFilter::make('shop_id')
                    ->label('店舗')
                    ->relationship('shop', 'name')
                    ->searchable(),
            ])
            ->actions([
                Action::make('approve')
                    ->label('承認')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ShopImage $record) => $record->status !== 'published')
                    ->action(function (ShopImage $record) {
                        $record->approve(auth()->id());
                        Notification::make()
                            ->title('画像を承認しました')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('却下')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (ShopImage $record) => $record->status !== 'rejected')
                    ->action(function (ShopImage $record) {
                        $record->reject(auth()->id());
                        Notification::make()
                            ->title('画像を却下しました')
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => Pages\ListShopImages::route('/'),
            'create' => Pages\CreateShopImage::route('/create'),
            'edit' => Pages\EditShopImage::route('/{record}/edit'),
        ];
    }
}
