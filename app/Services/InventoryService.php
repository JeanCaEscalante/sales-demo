<?php

namespace App\Services;

use App\Models\Product;

class InventoryService
{
    private Product $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function updatePurchasePrice(float $price)
    {
        $this->product->setPriceIn($price);
    }

    public function updateSalePrice(float $price)
    {
        $this->product->setPriceOut($price);
    }

    public function addToStock(float $quantity)
    {
        $this->product->increase($quantity);
    }

    public function removeFromStock(float $quantity)
    {
        $this->product->decrease($quantity);
    }
}
