<?php

namespace App\Filament\Resources\ShopImageResource\Pages;

use App\Filament\Resources\ShopImageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShopImages extends ListRecords
{
    protected static string $resource = ShopImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
