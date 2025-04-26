<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitMeasure extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'unit_measures';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'unit_measure_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'constant',
        'description',
    ];
}
