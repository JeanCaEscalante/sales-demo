<?php

namespace App\Filament\Resources\TaxeResource\Pages;

use App\Filament\Resources\TaxeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTaxes extends ListRecords
{
    protected static string $resource = TaxeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
