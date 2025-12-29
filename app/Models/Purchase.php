<?php

namespace App\Models;

use App\Enums\TypeReceipt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $table = 'purchases';

    protected $primaryKey = 'purchase_id';

    protected $fillable = [
        'supplier_id',
        'user_id',
        'document_type',
        'series',
        'receipt_number',
        'purchase_date',
        'currency',
        'exchange_rate',
        'subtotal',
        'taxable_base',
        'total_exempt',
        'total_tax',
        'total_amount',
    ];

    protected $casts = [
        'document_type' => TypeReceipt::class,
        'purchase_date' => 'date',
        'exchange_rate' => 'decimal:4',
        'subtotal' => 'decimal:2',
        'taxable_base' => 'decimal:2',
        'total_exempt' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_id', 'purchase_id');
    }
}
