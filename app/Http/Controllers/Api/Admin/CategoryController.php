<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryRepositoryInterface $categories) {}

    #[OA\Get(
        path: '/admin/categories',
        tags: ['Admin Products'],
        summary: 'List all categories (admin)',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Category list')],
    )]
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        return response()->json(['data' => CategoryResource::collection($this->categories->all(['parent']))]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category = $this->categories->create($data);

        return response()->json(['data' => new CategoryResource($category)], 201);
    }

    public function show(Category $category): JsonResponse
    {
        $this->authorize('view', $category);

        return response()->json(['data' => new CategoryResource($category->load('children'))]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $this->categories->update($category, $data);

        return response()->json(['data' => new CategoryResource($category->fresh())]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('delete', $category);

        $this->categories->delete($category);

        return response()->json(['message' => 'Category deleted.']);
    }
}
