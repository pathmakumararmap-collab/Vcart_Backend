<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Resources\LowStockAlertResource;
use App\Http\Resources\StockMovementResource;
use App\Http\Resources\StockResource;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Repositories\Contracts\LowStockAlertRepositoryInterface;
use App\Repositories\Contracts\StockRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class StockController extends Controller
{
    public function __construct(
        private readonly StockRepositoryInterface $stocks,
        private readonly LowStockAlertRepositoryInterface $lowStockAlerts,
    ) {}

    #[OA\Get(
        path: '/inventory/stock',
        tags: ['Inventory'],
        summary: 'List current stock levels (optionally filtered by warehouse)',
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'warehouse_id', in: 'query', schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Stock list')],
    )]
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('stock.view'), 403);

        $query = Stock::query()->with(['product', 'variant', 'warehouse']);

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->integer('warehouse_id'));
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        $stocks = $query->paginate((int) $request->integer('per_page', 20));

        return response()->json(StockResource::collection($stocks)->response()->getData(true));
    }

    #[OA\Get(
        path: '/inventory/stock/low-stock',
        tags: ['Inventory'],
        summary: 'List active low-stock alerts',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Low stock alert list')],
    )]
    public function lowStock(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('stock.view'), 403);

        $alerts = $this->lowStockAlerts->paginate(
            (int) $request->integer('per_page', 20),
            ['product', 'warehouse'],
            [],
        );

        return response()->json(LowStockAlertResource::collection($alerts)->response()->getData(true));
    }

    #[OA\Get(
        path: '/inventory/stock/movements',
        tags: ['Inventory'],
        summary: 'List the stock movement ledger (audit trail for every inventory change)',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Movement list')],
    )]
    public function movements(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('stock.view'), 403);

        $query = StockMovement::query()->with(['product', 'warehouse'])->latest();

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->integer('warehouse_id'));
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        $movements = $query->paginate((int) $request->integer('per_page', 20));

        return response()->json(StockMovementResource::collection($movements)->response()->getData(true));
    }
}
