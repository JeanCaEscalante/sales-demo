<?php

namespace App\Services;

use App\Models\Article;

class InventoryService
{
    private Article $article;

    public function __construct(Article $article)
    {
        $this->article = $article;
    }

    public function updateIncomePrice(float $price)
    {
        $this->article->setPriceIn($price);
    }

    public function updateSalePrice(float $price)
    {
        $this->article->setPriceOut($price);
    }

    public function addToStock(float $quantity)
    {
        $this->article->increase($quantity);
    }

    public function removeFromStock(float $quantity)
    {
        $this->article->decrease($quantity);
    }
}
