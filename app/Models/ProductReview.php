<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class ProductReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'user_id', 'rating', 'title', 'comment',
        'status', 'moderated_by', 'moderated_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'moderated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * @return HasMany<ReviewImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ReviewImage::class)->orderBy('sort_order');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }
}
