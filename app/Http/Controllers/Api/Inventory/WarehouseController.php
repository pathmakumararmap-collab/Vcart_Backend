<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreWarehouseRequest;
use App\Http\Requests\Warehouse\UpdateWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use App\Repositories\Contracts\WarehouseRepositoryInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class WarehouseController extends Controller
{
    public function __construct(private readonly WarehouseRepositoryInterface $warehouses) {}

    #[OA\Get(
        path: '/inventory/warehouses',
        tags: ['Inventory'],
        summary: 'List warehouses and outlets',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Warehouse list')],
    )]
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Warehouse::class);

        return response()->json(['data' => WarehouseResource::collection($this->warehouses->all())]);
    }

    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $warehouse = $this->warehouses->create($request->validated());

        return response()->json(['data' => new WarehouseResource($warehouse)], 201);
    }

    public function show(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('view', $warehouse);

        return response()->json(['data' => new WarehouseResource($warehouse)]);
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): JsonResponse
    {
        $this->warehouses->update($warehouse, $request->validated());

        return response()->json(['data' => new WarehouseResource($warehouse->fresh())]);
    }

    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('delete', $warehouse);

        $this->warehouses->delete($warehouse);

        return response()->json(['message' => 'Warehouse deleted.']);
    }
}
