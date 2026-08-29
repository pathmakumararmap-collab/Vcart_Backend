<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewImage extends Model
{
    protected $fillable = ['product_review_id', 'path', 'sort_order'];

    /**
     * @return BelongsTo<ProductReview, $this>
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(ProductReview::class, 'product_review_id');
    }
}
