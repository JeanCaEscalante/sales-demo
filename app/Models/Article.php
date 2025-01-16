<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'articles';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'article_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'code',
        'name',
        'stock',
        'price_in',
        'price_out',
        'description'
    ];

    /**
     * Get the category that owns the article.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

     /**
     * Get the order_history that owns the article.
     */
    public function incomes(): HasMany
    {
        return $this->hasMany(IncomeDetail::class, 'article_id');
    }

    public function setPriceIn(float $price): void
    {
        $this->price_in = $price;
        $this->save();
    }

    public function setPriceOut(float $price): void
    {
        $this->price_out = $price;
        $this->save();
    }

    public function increase(float $quantity): void
    {
        $this->stock += $quantity;
        $this->save();
    }

    public function decrease(float $quantity): void
    {
        $this->stock -= $quantity;
        $this->save();
    }
}
