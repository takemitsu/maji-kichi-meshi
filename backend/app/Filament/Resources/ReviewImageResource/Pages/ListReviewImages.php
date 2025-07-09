<?php

namespace App\Filament\Resources\ReviewImageResource\Pages;

use App\Filament\Resources\ReviewImageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReviewImages extends ListRecords
{
    protected static string $resource = ReviewImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
