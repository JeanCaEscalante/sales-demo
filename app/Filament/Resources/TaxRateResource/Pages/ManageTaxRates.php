<?php

namespace App\Filament\Resources\TaxRateResource\Pages;

use App\Filament\Resources\TaxRateResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTaxRates extends ManageRecords
{
    protected static string $resource = TaxRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
