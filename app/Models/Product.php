<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'products';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'product_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'unit_id',
        'code',
        'name',
        'stock',
        'price_in',
        'price_out',
        'description',
    ];

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Get the unit that owns the product.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /**
     * Get the purchase items for the product.
     */
    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'product_id');
    }

    /**
     * Get the sale items for the product.
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'product_id');
    }

    /**
     * Get the discounts for the product.
     */
    public function discounts()
    {
        return $this->morphMany(Discount::class, 'discountable');
    }

    public function setPriceIn(float $price): void
    {
        $this->price_in = $price;
        $this->save();
    }

    public function setPriceOut(float $price): void
    {
        $this->price_out = $price;
        $this->save();
    }

    public function increase(float $quantity): void
    {
        $this->stock += $quantity;
        $this->save();
    }

    public function decrease(float $quantity): void
    {
        $this->stock -= $quantity;
        $this->save();
    }
}
