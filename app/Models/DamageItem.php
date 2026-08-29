<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DamageItem extends Model
{
    protected $fillable = [
        'damage_id', 'product_id', 'product_variant_id', 'quantity', 'estimated_loss',
    ];

    protected function casts(): array
    {
        return [
            'estimated_loss' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Damage, $this>
     */
    public function damage(): BelongsTo
    {
        return $this->belongsTo(Damage::class);
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
