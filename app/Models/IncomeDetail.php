<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomeDetail extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'income_details';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'income_detail_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'income_id',
        'article_id',
        'quantity',
        'purchase_price',
        'sale_price',
    ];

    /**
     * Get the detail for the income.
     */
    public function income(): BelongsTo
    {
        return $this->BelongsTo(Income::class, 'income_id');
    }

    /**
     * Get the article for the IncomeDetail.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }
}
