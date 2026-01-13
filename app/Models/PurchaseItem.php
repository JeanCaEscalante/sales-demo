<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    protected $table = 'purchase_items';

    protected $primaryKey = 'purchase_item_id';

    protected $fillable = [
        'purchase_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount',
        'subtotal',
        'tax_exempt',
        'tax_rate_id',
        'tax_rate',
        'tax_name',
        'tax_amount',
        'profit',
        'update_sale_price',
        'sale_price',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:4',
        'discount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_exempt' => 'boolean',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'profit' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'update_sale_price' => 'boolean',
    ];

    /**
     * Evento que se ejecuta antes de guardar el modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($purchaseItem) {
            // Si el toggle está desactivado, asegurar que el precio de venta sea null
            if (! $purchaseItem->update_sale_price) {
                $purchaseItem->sale_price = null;
            }

            // Si el toggle está activo pero no hay precio, no permitir guardar
            if ($purchaseItem->update_sale_price && empty($purchaseItem->sale_price)) {
                throw new \InvalidArgumentException('El precio de venta es requerido cuando se activa la actualización');
            }
        });
    }

    /**
     * Multiplicador de descuento: (1 - discount/100)
     * Uso: $item->discount_multiplier
     */
    protected function discountMultiplier(): Attribute
    {
        return Attribute::get(
            fn () => 1 - (($this->discount ?? 0) / 100)
        );
    }

    /**
     * Precio unitario con descuento aplicado.
     * Uso: $item->unit_price_discounted
     */
    protected function unitPriceDiscounted(): Attribute
    {
        return Attribute::get(
            fn () => ($this->unit_price ?? 0) * $this->discount_multiplier
        );
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id', 'purchase_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax_rate_id', 'tax_rate_id');
    }
}
