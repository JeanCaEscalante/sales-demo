<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use App\Enums\TypeMovement;

class InventoryService
{
    private Product $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function updatePurchasePrice(float $price)
    {
        $this->product->setUnitPrice($price);
    }

    public function updateSalePrice(float $price)
    {
        $this->product->setSalePrice($price);
    }

    public function addToStock(float $quantity, string $reason = 'Compra', $reference = null, ?string $notes = null)
    {
        $previousStock = $this->product->stock;
        $this->product->increase($quantity);
        $newStock = $this->product->stock;

        $this->recordMovement(TypeMovement::INPUT, $quantity, $previousStock, $newStock, $reason, $reference, $notes);
    }

    public function removeFromStock(float $quantity, string $reason = 'Venta', $reference = null, ?string $notes = null)
    {
        $previousStock = $this->product->stock;
        $this->product->decrease($quantity);
        $newStock = $this->product->stock;

        $this->recordMovement(TypeMovement::OUTPUT, $quantity, $previousStock, $newStock, $reason, $reference, $notes);
    }

    private function recordMovement(TypeMovement $type, float $quantity, float $previousStock, float $newStock, string $reason, $reference = null, ?string $notes = null)
    {
        InventoryMovement::create([
            'product_id' => $this->product->getKey(), // Usar getKey() del producto también
            'user_id' => Auth::id(),
            'type' => $type,
            'quantity' => $quantity,
            'previous_stock' => $previousStock,
            'new_stock' => $newStock,
            'reason' => $reason,
            'referenceable_id' => $reference?->getKey(),
            'referenceable_type' => get_class($reference),
            'notes' => $notes,
        ]);
    }
}
