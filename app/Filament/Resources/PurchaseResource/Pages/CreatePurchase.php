<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use App\Models\Product;
use App\Services\InventoryService;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function afterCreate(): void
    {
        $purchase = $this->getRecord();

        $purchase->items->each(function ($item) use ($purchase) {
            $product = Product::find($item->product_id);
            $inventoryService = new InventoryService($product);
            $inventoryService->addToStock($item->quantity, 'Compra', $purchase);
            $inventoryService->updatePurchasePrice($item->unit_price);
            $inventoryService->updateSalePrice($item->sale_price);
        });
    }
}
