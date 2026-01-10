<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use App\Models\Currency;
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
            
            // Siempre actualizar stock
            $inventoryService->addToStock($item->quantity, 'Compra', $purchase);
            
            // Convertir precio de compra a moneda base usando la TASA DE LA FACTURA
            // Importante: Usamos $purchase->exchange_rate (tasa ingresada manualmente)
            // no la tasa actual del sistema, para reflejar el costo real en el momento de compra
            $purchasePriceInBase = $this->convertToBaseCurrency($item->unit_price, $purchase->exchange_rate);
            $inventoryService->updatePurchasePrice($purchasePriceInBase);
            
            // Actualizar precio de venta SOLO si el toggle está activo
            if ($item->update_sale_price && !empty($item->sale_price)) {
                // El sale_price ya viene en moneda base por el cálculo del formulario
                // usando la misma tasa de cambio de la factura
                $inventoryService->updateSalePrice($item->sale_price);
            }
        });
    }

    /**
     * Convierte un monto de moneda de factura a moneda base
     */
    private function convertToBaseCurrency(float $amount, float $exchangeRate): float
    {
        if ($exchangeRate <= 0) {
            throw new \InvalidArgumentException('Tasa de cambio inválida: ' . $exchangeRate);
        }
        
        return round($amount / $exchangeRate, 4);
    }
}
