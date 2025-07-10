<?php

namespace App\Filament\Resources\ShopImageResource\Pages;

use App\Filament\Resources\ShopImageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShopImage extends EditRecord
{
    protected static string $resource = ShopImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
