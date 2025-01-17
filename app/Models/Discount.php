<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'discounts';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'discount_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'discount_code',
        'discount_type',
        'discount_value',
        'start_date',
        'end_date',
        'min_amount',
        'max_uses',
        'used',
        'discountable',
        'is_active',
        'user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
