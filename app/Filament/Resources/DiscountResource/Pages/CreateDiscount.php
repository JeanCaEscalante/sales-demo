<?php

namespace App\Filament\Resources\DiscountResource\Pages;

use App\Filament\Resources\DiscountResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateDiscount extends CreateRecord
{
    protected static string $resource = DiscountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['used'] = 0;
        $data['is_active'] = true;
        $data['user_id'] = Auth::id();

        return $data;
    }
}
