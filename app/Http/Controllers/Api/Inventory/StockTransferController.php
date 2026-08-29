<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockTransfer\ReceiveStockTransferRequest;
use App\Http\Requests\StockTransfer\StoreStockTransferRequest;
use App\Http\Resources\StockTransferResource;
use App\Models\StockTransfer;
use App\Repositories\Contracts\StockTransferRepositoryInterface;
use App\Services\StockTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class StockTransferController extends Controller
{
    public function __construct(
        private readonly StockTransferRepositoryInterface $transfers,
        private readonly StockTransferService $transferService,
    ) {}

    #[OA\Get(
        path: '/inventory/stock-transfers',
        tags: ['Inventory'],
        summary: 'List stock transfers between warehouses',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Transfer list')],
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StockTransfer::class);

        $transfers = $this->transfers->paginate((int) $request->integer('per_page', 15), ['fromWarehouse', 'toWarehouse']);

        return response()->json(StockTransferResource::collection($transfers)->response()->getData(true));
    }

    #[OA\Post(
        path: '/inventory/stock-transfers',
        tags: ['Inventory'],
        summary: 'Dispatch a stock transfer (deducts source warehouse immediately)',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 201, description: 'Created and dispatched')],
    )]
    public function store(StoreStockTransferRequest $request): JsonResponse
    {
        $transfer = $this->transferService->create($request->validated(), $request->user()->id);

        return response()->json(['data' => new StockTransferResource($transfer)], 201);
    }

    public function show(StockTransfer $stockTransfer): JsonResponse
    {
        $this->authorize('view', $stockTransfer);

        return response()->json(['data' => new StockTransferResource($stockTransfer->load(['fromWarehouse', 'toWarehouse', 'items.product', 'items.variant']))]);
    }

    #[OA\Post(
        path: '/inventory/stock-transfers/{stockTransfer}/receive',
        tags: ['Inventory'],
        summary: 'Receive a stock transfer at the destination warehouse',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Received')],
    )]
    public function receive(ReceiveStockTransferRequest $request, StockTransfer $stockTransfer): JsonResponse
    {
        $this->authorize('update', $stockTransfer);

        $transfer = $this->transferService->receive($stockTransfer, $request->validated('items'), $request->user()->id);

        return response()->json(['data' => new StockTransferResource($transfer)]);
    }
}
