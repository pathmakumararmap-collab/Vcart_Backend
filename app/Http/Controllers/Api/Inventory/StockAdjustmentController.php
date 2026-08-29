<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockAdjustment\StoreStockAdjustmentRequest;
use App\Http\Resources\StockAdjustmentResource;
use App\Models\StockAdjustment;
use App\Repositories\Contracts\StockAdjustmentRepositoryInterface;
use App\Services\StockAdjustmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class StockAdjustmentController extends Controller
{
    public function __construct(
        private readonly StockAdjustmentRepositoryInterface $adjustments,
        private readonly StockAdjustmentService $adjustmentService,
    ) {}

    #[OA\Get(
        path: '/inventory/stock-adjustments',
        tags: ['Inventory'],
        summary: 'List stock adjustments (physical counts, corrections)',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Adjustment list')],
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StockAdjustment::class);

        $adjustments = $this->adjustments->paginate((int) $request->integer('per_page', 15), ['warehouse']);

        return response()->json(StockAdjustmentResource::collection($adjustments)->response()->getData(true));
    }

    #[OA\Post(
        path: '/inventory/stock-adjustments',
        tags: ['Inventory'],
        summary: 'Create a stock adjustment (sets exact quantities)',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 201, description: 'Created')],
    )]
    public function store(StoreStockAdjustmentRequest $request): JsonResponse
    {
        $adjustment = $this->adjustmentService->create($request->validated(), $request->user()->id);

        return response()->json(['data' => new StockAdjustmentResource($adjustment)], 201);
    }

    public function show(StockAdjustment $stockAdjustment): JsonResponse
    {
        $this->authorize('view', $stockAdjustment);

        return response()->json(['data' => new StockAdjustmentResource($stockAdjustment->load(['warehouse', 'items.product', 'items.variant']))]);
    }
}
