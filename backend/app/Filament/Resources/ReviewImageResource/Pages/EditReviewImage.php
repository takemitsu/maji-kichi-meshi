<?php

namespace App\Filament\Resources\ReviewImageResource\Pages;

use App\Filament\Resources\ReviewImageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReviewImage extends EditRecord
{
    protected static string $resource = ReviewImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
