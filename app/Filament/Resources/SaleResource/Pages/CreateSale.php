<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Product;
use App\Services\InventoryService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $sale = $this->getRecord();

        $sale->items->each(function ($item) use ($sale) {
            $product = Product::find($item->product_id);
            $service = new InventoryService($product);
            $service->removeFromStock($item->quantity, 'Venta', $sale);
        });

    }
}
