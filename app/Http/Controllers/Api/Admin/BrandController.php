<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Brand\StoreBrandRequest;
use App\Http\Requests\Brand\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Repositories\Contracts\BrandRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class BrandController extends Controller
{
    public function __construct(private readonly BrandRepositoryInterface $brands) {}

    #[OA\Get(
        path: '/admin/brands',
        tags: ['Admin Products'],
        summary: 'List all brands (admin)',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Brand list')],
    )]
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Brand::class);

        return response()->json(['data' => BrandResource::collection($this->brands->all())]);
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }

        $brand = $this->brands->create($data);

        return response()->json(['data' => new BrandResource($brand)], 201);
    }

    public function show(Brand $brand): JsonResponse
    {
        $this->authorize('view', $brand);

        return response()->json(['data' => new BrandResource($brand)]);
    }

    public function update(UpdateBrandRequest $request, Brand $brand): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }

        $this->brands->update($brand, $data);

        return response()->json(['data' => new BrandResource($brand->fresh())]);
    }

    public function destroy(Brand $brand): JsonResponse
    {
        $this->authorize('delete', $brand);

        $this->brands->delete($brand);

        return response()->json(['message' => 'Brand deleted.']);
    }
}
