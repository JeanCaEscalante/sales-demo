<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Enums\TypeSubject;
use App\Filament\Resources\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type_subject'] = TypeSubject::Customer;

        return $data;
    }
}
