<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Powers the Fast / Slow / Non-moving item reports.
 *
 * "Moving" is based on units sold (order_items.quantity) within the selected
 * date range, across non-cancelled/non-returned orders. Classification
 * thresholds are configurable via Settings (see thresholds()/updateThresholds()).
 */
class MovementReportService
{
    private const SETTINGS_GROUP = 'movement_report';

    private const DEFAULTS = [
        'mode' => 'percentile',            // 'percentile' | 'fixed'
        'fast_percentile' => 20,            // top 20% by units sold = fast
        'slow_percentile' => 20,            // bottom 20% (excluding zero) = slow
        'fast_qty_threshold' => 50,         // fixed mode: qty >= this = fast
        'slow_qty_threshold' => 10,         // fixed mode: 0 < qty <= this = slow
    ];

    /**
     * @return array<string, int|string>
     */
    public function thresholds(): array
    {
        $values = [];
        foreach (self::DEFAULTS as $key => $default) {
            $stored = Setting::getValue(self::SETTINGS_GROUP.'.'.$key, $default);
            $values[$key] = $key === 'mode' ? (string) $stored : (int) $stored;
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, int|string>
     */
    public function updateThresholds(array $input): array
    {
        foreach (self::DEFAULTS as $key => $default) {
            if (array_key_exists($key, $input)) {
                Setting::query()->updateOrCreate(
                    ['key' => self::SETTINGS_GROUP.'.'.$key],
                    ['value' => (string) $input[$key], 'group' => self::SETTINGS_GROUP],
                );
            }
        }

        return $this->thresholds();
    }

    /**
     * @param  array{
     *     from?: string|null, to?: string|null, warehouse_id?: int|null,
     *     warehouse_type?: string|null, category_id?: int|null, brand_id?: int|null,
     *     group_by?: string|null, bucket?: string|null,
     * }  $filters
     * @return array<string, mixed>
     */
    public function report(array $filters): array
    {
        [$from, $to] = $this->resolveDateRange($filters['from'] ?? null, $filters['to'] ?? null);
        $thresholds = $this->thresholds();

        $salesSub = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereNotIn('orders.status', ['cancelled', 'returned'])
            ->when(
                $filters['warehouse_id'] ?? null,
                fn ($q, $warehouseId) => $q->where('order_items.warehouse_id', $warehouseId)
            )
            ->when($filters['warehouse_type'] ?? null, function ($q, $type) {
                $ids = DB::table('warehouses')->where('type', $type)->pluck('id');
                $q->whereIn('order_items.warehouse_id', $ids);
            })
            ->select(
                'order_items.product_id',
                DB::raw('SUM(order_items.quantity) as qty_sold'),
                DB::raw('SUM(order_items.subtotal) as revenue'),
            )
            ->groupBy('order_items.product_id');

        $rows = DB::table('products')
            ->leftJoinSub($salesSub, 'sales', 'sales.product_id', '=', 'products.id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('categories as parent_categories', 'parent_categories.id', '=', 'categories.parent_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->when(
                $filters['category_id'] ?? null,
                fn ($q, $categoryId) => $q->where('products.category_id', $categoryId)
            )
            ->when($filters['brand_id'] ?? null, fn ($q, $brandId) => $q->where('products.brand_id', $brandId))
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                'products.sku',
                'products.category_id',
                'categories.name as category_name',
                DB::raw('COALESCE(parent_categories.id, categories.id) as top_category_id'),
                DB::raw('COALESCE(parent_categories.name, categories.name) as top_category_name'),
                'products.brand_id',
                'brands.name as brand_name',
                DB::raw('COALESCE(sales.qty_sold, 0) as qty_sold'),
                DB::raw('COALESCE(sales.revenue, 0) as revenue'),
            )
            ->get();

        $classified = $this->classify($rows, $thresholds);

        $groupBy = $filters['group_by'] ?? null;
        $result = $groupBy
            ? $this->groupRows($classified, $groupBy)
            : $classified->values();

        if (! $groupBy && ($filters['bucket'] ?? null)) {
            $result = $result->where('bucket', $filters['bucket'])->values();
        }

        return [
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'thresholds' => $thresholds,
            'group_by' => $groupBy,
            'rows' => $result,
        ];
    }

    /**
     * Attaches a `bucket` (fast/slow/non_moving/null) to every row.
     */
    private function classify(Collection $rows, array $thresholds): Collection
    {
        if ($thresholds['mode'] === 'fixed') {
            return $rows->map(function ($row) use ($thresholds) {
                $row->bucket = match (true) {
                    $row->qty_sold <= 0 => 'non_moving',
                    $row->qty_sold >= $thresholds['fast_qty_threshold'] => 'fast',
                    $row->qty_sold <= $thresholds['slow_qty_threshold'] => 'slow',
                    default => null,
                };

                return $row;
            });
        }

        // Percentile mode: rank only the products that sold at all.
        $withSales = $rows->filter(fn ($row) => $row->qty_sold > 0)->sortByDesc('qty_sold')->values();
        $total = $withSales->count();
        $fastCutoff = (int) ceil($total * ($thresholds['fast_percentile'] / 100));
        $slowCutoff = (int) ceil($total * ($thresholds['slow_percentile'] / 100));

        $bucketByProductId = [];
        foreach ($withSales as $index => $row) {
            $bucketByProductId[$row->product_id] = match (true) {
                $index < $fastCutoff => 'fast',
                $index >= $total - $slowCutoff => 'slow',
                default => null,
            };
        }

        return $rows->map(function ($row) use ($bucketByProductId) {
            $row->bucket = $row->qty_sold <= 0 ? 'non_moving' : ($bucketByProductId[$row->product_id] ?? null);

            return $row;
        });
    }

    private function groupRows(Collection $rows, string $groupBy): Collection
    {
        [$idField, $nameField] = match ($groupBy) {
            'category' => ['top_category_id', 'top_category_name'],
            'subcategory' => ['category_id', 'category_name'],
            'brand' => ['brand_id', 'brand_name'],
            default => ['category_id', 'category_name'],
        };

        return $rows
            ->groupBy($idField)
            ->map(function (Collection $group, $groupId) use ($nameField) {
                return (object) [
                    'group_id' => $groupId ?: null,
                    'group_name' => $group->first()->{$nameField} ?? 'Uncategorized',
                    'products_count' => $group->count(),
                    'qty_sold' => $group->sum('qty_sold'),
                    'revenue' => $group->sum('revenue'),
                    'fast_count' => $group->where('bucket', 'fast')->count(),
                    'slow_count' => $group->where('bucket', 'slow')->count(),
                    'non_moving_count' => $group->where('bucket', 'non_moving')->count(),
                ];
            })
            ->sortByDesc('qty_sold')
            ->values();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveDateRange(?string $from, ?string $to): array
    {
        $to = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();
        $from = $from ? Carbon::parse($from)->startOfDay() : $to->copy()->subDays(29)->startOfDay();

        return [$from, $to];
    }
}
