<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Taxe extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'taxes';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'taxe_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'country',
        'state',
        'name',
        'rate',
        'priority',
        'is_composed',
        'is_shipping'
    ];

    protected $casts = [
        'is_composed' => 'boolean',
        'is_shipping' => 'boolean',
    ];

}
