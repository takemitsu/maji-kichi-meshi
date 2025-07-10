<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'カテゴリ';

    protected static ?string $modelLabel = 'カテゴリ';

    protected static ?string $pluralModelLabel = 'カテゴリ';

    protected static ?string $navigationGroup = 'コンテンツ管理';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('カテゴリ名')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $context, $state, callable $set) {
                        if ($context === 'create' || $context === 'edit') {
                            $set('slug', Str::slug($state));
                        }
                    }),

                TextInput::make('slug')
                    ->label('スラッグ')
                    ->required()
                    ->maxLength(255)
                    ->unique(Category::class, 'slug', ignoreRecord: true)
                    ->helperText('URL用の識別子（英数字とハイフン）'),

                Select::make('type')
                    ->label('カテゴリタイプ')
                    ->options([
                        'basic' => '基本カテゴリ',
                        'time' => '時間帯タグ',
                        'ranking' => 'ランキング用',
                    ])
                    ->default('basic')
                    ->required(),

                Textarea::make('description')
                    ->label('説明')
                    ->rows(3)
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('有効')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('カテゴリ名')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('スラッグ')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('type')
                    ->label('タイプ')
                    ->colors([
                        'primary' => 'basic',
                        'success' => 'time',
                        'warning' => 'ranking',
                    ])
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'basic' => '基本カテゴリ',
                            'time' => '時間帯タグ',
                            'ranking' => 'ランキング用',
                            default => $state,
                        };
                    }),

                TextColumn::make('shops_count')
                    ->label('店舗数')
                    ->counts('shops')
                    ->sortable(),

                TextColumn::make('rankings_count')
                    ->label('ランキング数')
                    ->counts('rankings')
                    ->sortable(),

                BadgeColumn::make('is_active')
                    ->label('状態')
                    ->colors([
                        'success' => true,
                        'danger' => false,
                    ])
                    ->formatStateUsing(function ($state) {
                        return $state ? '有効' : '無効';
                    }),

                TextColumn::make('created_at')
                    ->label('作成日')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('更新日')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('タイプ')
                    ->options([
                        'basic' => '基本カテゴリ',
                        'time' => '時間帯タグ',
                        'ranking' => 'ランキング用',
                    ]),

                SelectFilter::make('is_active')
                    ->label('状態')
                    ->options([
                        true => '有効',
                        false => '無効',
                    ]),
            ])
            ->actions([
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
