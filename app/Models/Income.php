<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Income extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'incomes';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'income_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'subject_id',
        'type_receipt',
        'receipt_series',
        'num_receipt',
        'receipt_at',
        'tax',
        'total_purchase',
        'user_id',
    ];

    /**
     * Get the detail for the income.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    /**
     * Get the detail for the income.
     */
    public function details(): HasMany
    {
        return $this->hasMany(IncomeDetail::class, 'income_id');
    }

    /**
     * Get the user for the subject.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
