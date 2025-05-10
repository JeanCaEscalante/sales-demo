<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasUlids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'documents';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'document_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'subject_id',
        'user_id',
        'type_receipt',
        'document_series',
        'document_num',
        'tax',
        'total_sale',
    ];

    /**
     * Get the detail for the income.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    /**
     * Get the detail for the income.
     */
    public function details(): HasMany
    {
        return $this->hasMany(DocumentDetail::class, 'sale_id');
    }

    /**
     * Get the user for the subject.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
