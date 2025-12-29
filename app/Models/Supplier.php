<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Supplier extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'suppliers';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'supplier_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type_document',
        'document',
        'name',
        'address',
        'notes'
    ];

    /**
     * Get the contacts for the supplier.
     */
    public function contacts(): MorphMany
    {
        return $this->morphMany(Contact::class, 'contactable');
    }

    /**
     * Get the purchases for the supplier.
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'supplier_id');
    }
}
