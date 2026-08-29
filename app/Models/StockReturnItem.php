<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReturnItem extends Model
{
    protected $fillable = [
        'stock_return_id', 'product_id', 'product_variant_id', 'quantity', 'condition',
    ];

    /**
     * @return BelongsTo<StockReturn, $this>
     */
    public function stockReturn(): BelongsTo
    {
        return $this->belongsTo(StockReturn::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
