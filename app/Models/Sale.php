<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Sale extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sales';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'sale_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'user_id',
        'document_type',
        'series',
        'number',
        'sale_date',
        'subtotal',
        'taxable_base',
        'total_exempt',
        'total_tax',
        'total_discounts',
        'total_amount',
        'payment_status',
        'paid_amount',
        'balance',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payment_status' => \App\Enums\TypePaymentStatus::class,
        'sale_date' => 'date',
        'subtotal' => 'decimal:2',
        'taxable_base' => 'decimal:2',
        'total_exempt' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total_discounts' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    /**
     * Get the customer for the sale.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    /**
     * Get the items for the sale.
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'sale_id', 'sale_id');
    }

    /**
     * Get the user for the sale.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the payments for the sale.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class, 'sale_id', 'sale_id');
    }

    /**
     * Scope a query to only include records for the authenticated user.
     */
    public function scopeAuthenticated(Builder $query): Builder
    {
        return $query->where('user_id', Auth::id());
    }


    public function updatePaymentStatus(): void
    {
        $this->paid_amount = $this->payments()->sum('amount');
        $this->balance = $this->total_amount - $this->paid_amount;
        
        if ($this->balance <= 0) {
            $this->payment_status = 'paid';
        } elseif ($this->paid_amount > 0) {
            $this->payment_status = 'partial';
        } else {
            $this->payment_status = 'pending';
        }
        
        $this->save();
    }

    // Scope para ventas pendientes
    public function scopePending($query)
    {
        return $query->whereIn('payment_status', ['pending', 'partial']);
}
}
