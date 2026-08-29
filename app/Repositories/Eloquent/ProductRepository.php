<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function findBySku(string $sku): ?Model
    {
        return $this->model->newQuery()->where('sku', $sku)->first();
    }

    public function findByBarcode(string $barcode): ?Model
    {
        return $this->model->newQuery()->where('barcode', $barcode)->first();
    }

    public function findBySlug(string $slug): ?Model
    {
        return $this->model->newQuery()->where('slug', $slug)->first();
    }

    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with(['category', 'brand', 'images', 'stocks'])
            ->withAvg('approvedReviews as reviews_avg_rating', 'rating')
            ->withCount('approvedReviews as reviews_count');

        return $this->applyFilters($query, $filters)->paginate($perPage);
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function (Builder $q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('sku', 'like', "%{$keyword}%")
                    ->orWhere('barcode', 'like', "%{$keyword}%");
            });
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (! empty($filters['is_featured'])) {
            $query->where('is_featured', true);
        }

        if (! empty($filters['min_price'])) {
            $query->where('selling_price', '>=', $filters['min_price']);
        }

        if (! empty($filters['max_price'])) {
            $query->where('selling_price', '<=', $filters['max_price']);
        }

        $sort = $filters['sort'] ?? 'latest';

        match ($sort) {
            'price_asc' => $query->orderBy('selling_price', 'asc'),
            'price_desc' => $query->orderBy('selling_price', 'desc'),
            'name' => $query->orderBy('name', 'asc'),
            default => $query->latest(),
        };

        return $query;
    }
}
