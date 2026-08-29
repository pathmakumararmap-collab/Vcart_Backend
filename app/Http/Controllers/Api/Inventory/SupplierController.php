<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SupplierController extends Controller
{
    public function __construct(private readonly SupplierRepositoryInterface $suppliers) {}

    #[OA\Get(
        path: '/inventory/suppliers',
        tags: ['Inventory'],
        summary: 'List suppliers',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Supplier list')],
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Supplier::class);

        return response()->json($this->suppliers->paginate((int) $request->integer('per_page', 15))->toArray());
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = $this->suppliers->create($request->validated());

        return response()->json(['data' => new SupplierResource($supplier)], 201);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        $this->authorize('view', $supplier);

        return response()->json(['data' => new SupplierResource($supplier)]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $this->suppliers->update($supplier, $request->validated());

        return response()->json(['data' => new SupplierResource($supplier->fresh())]);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $this->authorize('delete', $supplier);

        $this->suppliers->delete($supplier);

        return response()->json(['message' => 'Supplier deleted.']);
    }
}
