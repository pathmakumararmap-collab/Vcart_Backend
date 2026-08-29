<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\ReceivePurchaseRequest;
use App\Http\Requests\Purchase\StorePurchaseRequest;
use App\Http\Resources\PurchaseResource;
use App\Models\Purchase;
use App\Repositories\Contracts\PurchaseRepositoryInterface;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly PurchaseRepositoryInterface $purchases,
        private readonly PurchaseService $purchaseService,
    ) {}

    #[OA\Get(
        path: '/inventory/purchases',
        tags: ['Inventory'],
        summary: 'List purchase orders',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Purchase list')],
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Purchase::class);

        $purchases = $this->purchases->paginate((int) $request->integer('per_page', 15), ['supplier', 'warehouse']);

        return response()->json(PurchaseResource::collection($purchases)->response()->getData(true));
    }

    #[OA\Post(
        path: '/inventory/purchases',
        tags: ['Inventory'],
        summary: 'Create a purchase order from a supplier',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 201, description: 'Created')],
    )]
    public function store(StorePurchaseRequest $request): JsonResponse
    {
        $purchase = $this->purchaseService->create($request->validated(), $request->user()->id);

        return response()->json(['data' => new PurchaseResource($purchase)], 201);
    }

    public function show(Purchase $purchase): JsonResponse
    {
        $this->authorize('view', $purchase);

        return response()->json(['data' => new PurchaseResource($purchase->load(['supplier', 'warehouse', 'items.product', 'items.variant']))]);
    }

    #[OA\Post(
        path: '/inventory/purchases/{purchase}/receive',
        tags: ['Inventory'],
        summary: 'Receive stock for a purchase order (increases central inventory)',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Stock received')],
    )]
    public function receive(ReceivePurchaseRequest $request, Purchase $purchase): JsonResponse
    {
        $this->authorize('update', $purchase);

        $purchase = $this->purchaseService->receive($purchase, $request->validated('items'), $request->user()->id);

        return response()->json(['data' => new PurchaseResource($purchase)]);
    }

    public function cancel(Purchase $purchase): JsonResponse
    {
        $this->authorize('update', $purchase);

        $purchase = $this->purchaseService->cancel($purchase);

        return response()->json(['data' => new PurchaseResource($purchase)]);
    }
}
