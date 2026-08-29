<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * @return array<string, mixed>
     */
    public function salesReport(?string $from = null, ?string $to = null, ?string $source = null, ?int $warehouseId = null): array
    {
        [$from, $to] = $this->resolveDateRange($from, $to);

        $query = Order::query()
            ->whereBetween('orders.created_at', [$from, $to])
            ->where('status', '!=', 'cancelled');

        if ($source) {
            $query->where('source', $source);
        }

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $summary = (clone $query)->selectRaw('
            COUNT(*) as total_orders,
            COALESCE(SUM(subtotal), 0) as total_subtotal,
            COALESCE(SUM(discount_amount), 0) as total_discount,
            COALESCE(SUM(tax_amount), 0) as total_tax,
            COALESCE(SUM(shipping_amount), 0) as total_shipping,
            COALESCE(SUM(total_amount), 0) as total_sales,
            COALESCE(SUM(paid_amount), 0) as total_collected,
            COALESCE(AVG(total_amount), 0) as average_order_value
        ')->first();

        $bySource = (clone $query)
            ->select('source', DB::raw('COUNT(*) as orders_count'), DB::raw('COALESCE(SUM(total_amount), 0) as total_sales'))
            ->groupBy('source')
            ->get();

        $byDay = (clone $query)
            ->select(DB::raw('DATE(orders.created_at) as date'), DB::raw('COUNT(*) as orders_count'), DB::raw('COALESCE(SUM(total_amount), 0) as total_sales'))
            ->groupBy(DB::raw('DATE(orders.created_at)'))
            ->orderBy('date')
            ->get();

        return [
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'summary' => $summary,
            'by_source' => $bySource,
            'by_day' => $byDay,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function orderReport(?string $from = null, ?string $to = null): array
    {
        [$from, $to] = $this->resolveDateRange($from, $to);

        $byStatus = Order::query()
            ->whereBetween('created_at', [$from, $to])
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get();

        $byPaymentStatus = Order::query()
            ->whereBetween('created_at', [$from, $to])
            ->select('payment_status', DB::raw('COUNT(*) as total'))
            ->groupBy('payment_status')
            ->get();

        return [
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'by_status' => $byStatus,
            'by_payment_status' => $byPaymentStatus,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function productReport(?string $from = null, ?string $to = null, int $limit = 20): array
    {
        [$from, $to] = $this->resolveDateRange($from, $to);

        $topSelling = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->where('orders.status', '!=', 'cancelled')
            ->select(
                'order_items.product_id',
                DB::raw('MAX(order_items.product_name) as product_name'),
                DB::raw('SUM(order_items.quantity) as units_sold'),
                DB::raw('SUM(order_items.subtotal) as revenue'),
            )
            ->groupBy('order_items.product_id')
            ->orderByDesc('units_sold')
            ->limit($limit)
            ->get();

        $stockValuation = Product::query()
            ->join('stocks', 'stocks.product_id', '=', 'products.id')
            ->select(DB::raw('COALESCE(SUM(stocks.quantity * products.cost_price), 0) as total_cost_value'))
            ->first();

        return [
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'top_selling' => $topSelling,
            'total_stock_cost_value' => $stockValuation->total_cost_value ?? 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function stockReport(?int $warehouseId = null): array
    {
        $stockQuery = Stock::query()->with(['product', 'variant', 'warehouse']);

        if ($warehouseId) {
            $stockQuery->where('warehouse_id', $warehouseId);
        }

        $lowStock = (clone $stockQuery)
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->whereColumn('stocks.quantity', '<=', 'products.low_stock_threshold')
            ->select('stocks.*')
            ->get();

        $totalUnits = (clone $stockQuery)->sum('quantity');

        $recentMovements = StockMovement::query()
            ->with(['product', 'warehouse'])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->latest()
            ->limit(50)
            ->get();

        return [
            'total_units' => $totalUnits,
            'low_stock_count' => $lowStock->count(),
            'low_stock_items' => $lowStock,
            'recent_movements' => $recentMovements,
        ];
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
