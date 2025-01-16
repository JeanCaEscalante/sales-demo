<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Enums\TypeSubject;
use App\Filament\Resources\SupplierResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplier extends CreateRecord
{
    protected static string $resource = SupplierResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type_subject'] = TypeSubject::Supplier;

        return $data;
    }
}
