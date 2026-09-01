<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class BrandController extends Controller
{
    #[OA\Get(
        path: '/catalog/brands',
        tags: ['Catalog'],
        summary: 'List active brands (public)',
        responses: [new OA\Response(response: 200, description: 'Brand list')],
    )]
    public function index(): JsonResponse
    {
        $brands = Brand::query()->where('is_active', true)->orderBy('name')->get();

        return response()->json(['data' => BrandResource::collection($brands)]);
    }
}