<?php

namespace App\Filament\Resources\TaxeResource\Pages;

use App\Filament\Resources\TaxeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaxe extends EditRecord
{
    protected static string $resource = TaxeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
