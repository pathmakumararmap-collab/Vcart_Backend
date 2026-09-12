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
        if (! empty($filters['keyword'])) {
            return $this->searchWithMeilisearch($filters, $perPage);
        }

        $query = $this->model->newQuery()
            ->with(['category', 'brand', 'images', 'stocks', 'variants:id,product_id,selling_price,is_active'])
            ->withAvg('approvedReviews as reviews_avg_rating', 'rating')
            ->withCount('approvedReviews as reviews_count');

        return $this->applyFilters($query, $filters)->paginate($perPage);
    }

    /**
     * Typo-tolerant, relevance-ranked search via Meilisearch (through
     * Scout). Category/brand/price/active/featured filters are compiled
     * into a single Meilisearch filter expression rather than chained
     * Scout `where()` calls, since Scout's fluent where() only supports
     * equality — price range needs a raw filter string either way.
     */
    protected function searchWithMeilisearch(array $filters, int $perPage): LengthAwarePaginator
    {
        $options = [];

        $filterExpression = $this->buildMeilisearchFilter($filters);
        if ($filterExpression !== '') {
            $options['filter'] = $filterExpression;
        }

        $options['sort'] = match ($filters['sort'] ?? 'latest') {
            'price_asc' => ['selling_price:asc'],
            'price_desc' => ['selling_price:desc'],
            'name' => ['name:asc'],
            default => ['created_at:desc'],
        };

        return Product::search($filters['keyword'])
            ->options($options)
            ->query(fn (Builder $query) => $query
                ->with(['category', 'brand', 'images', 'stocks', 'variants:id,product_id,selling_price,is_active'])
                ->withAvg('approvedReviews as reviews_avg_rating', 'rating')
                ->withCount('approvedReviews as reviews_count'))
            ->paginate($perPage);
    }

    protected function buildMeilisearchFilter(array $filters): string
    {
        $conditions = [];

        if (! empty($filters['category_id'])) {
            $conditions[] = 'category_id = '.(int) $filters['category_id'];
        }

        if (! empty($filters['brand_id'])) {
            $conditions[] = 'brand_id = '.(int) $filters['brand_id'];
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $conditions[] = 'is_active = '.($filters['is_active'] ? 'true' : 'false');
        }

        if (! empty($filters['is_featured'])) {
            $conditions[] = 'is_featured = true';
        }

        if (! empty($filters['min_price'])) {
            $conditions[] = 'selling_price >= '.(float) $filters['min_price'];
        }

        if (! empty($filters['max_price'])) {
            $conditions[] = 'selling_price <= '.(float) $filters['max_price'];
        }

        return implode(' AND ', $conditions);
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
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