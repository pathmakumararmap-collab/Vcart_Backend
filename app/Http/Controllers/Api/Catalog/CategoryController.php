<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryRepositoryInterface $categories) {}

    #[OA\Get(
        path: '/catalog/categories',
        tags: ['Catalog'],
        summary: 'List categories as a tree (public)',
        responses: [new OA\Response(response: 200, description: 'Category tree')],
    )]
    public function index(): JsonResponse
    {
        return response()->json(['data' => CategoryResource::collection($this->categories->tree())]);
    }
}
