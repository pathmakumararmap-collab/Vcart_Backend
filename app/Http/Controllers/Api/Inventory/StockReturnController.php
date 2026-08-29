<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockReturn\StoreStockReturnRequest;
use App\Http\Resources\StockReturnResource;
use App\Models\StockReturn;
use App\Repositories\Contracts\StockReturnRepositoryInterface;
use App\Services\StockReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class StockReturnController extends Controller
{
    public function __construct(
        private readonly StockReturnRepositoryInterface $returns,
        private readonly StockReturnService $returnService,
    ) {}

    #[OA\Get(
        path: '/inventory/stock-returns',
        tags: ['Inventory'],
        summary: 'List customer/supplier stock returns',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Return list')],
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StockReturn::class);

        $returns = $this->returns->paginate((int) $request->integer('per_page', 15), ['warehouse']);

        return response()->json(StockReturnResource::collection($returns)->response()->getData(true));
    }

    #[OA\Post(
        path: '/inventory/stock-returns',
        tags: ['Inventory'],
        summary: 'Record a stock return (good-condition items are added back to inventory)',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 201, description: 'Created')],
    )]
    public function store(StoreStockReturnRequest $request): JsonResponse
    {
        $return = $this->returnService->create($request->validated(), $request->user()->id);

        return response()->json(['data' => new StockReturnResource($return)], 201);
    }

    public function show(StockReturn $stockReturn): JsonResponse
    {
        $this->authorize('view', $stockReturn);

        return response()->json(['data' => new StockReturnResource($stockReturn->load(['warehouse', 'items.product', 'items.variant']))]);
    }
}
