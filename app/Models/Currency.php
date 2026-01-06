<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'currencies';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'currency_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'is_base',
        'is_active',
    ];

    protected $casts = [
        'is_base' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function exchangeRates(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'currency_id', 'currency_id');
    }

    public function getCurrentRate(): ?float
    {
        if ($this->is_base) {
            return 1.0;
        }

        // Validar primero que exista algún registro en la relación exchangeRates
        if (! $this->exchangeRates()->exists()) {
            return null;
        }

        $rateRecord = $this->exchangeRates()
            ->where('effective_date', '<=', now())
            ->latest('effective_date')
            ->first();

        return $rateRecord ? (float) $rateRecord->rate : null;
    }
}
