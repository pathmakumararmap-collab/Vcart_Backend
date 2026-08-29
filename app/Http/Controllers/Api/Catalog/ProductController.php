<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    public function __construct(private readonly ProductRepositoryInterface $products) {}

    #[OA\Get(
        path: '/catalog/products',
        tags: ['Catalog'],
        summary: 'Browse active products (public storefront)',
        parameters: [
            new OA\Parameter(name: 'keyword', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'brand_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string', enum: ['latest', 'price_asc', 'price_desc', 'name'])),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated product list')],
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['keyword', 'category_id', 'brand_id', 'min_price', 'max_price', 'sort']);
        $filters['is_active'] = true;

        $products = $this->products->search($filters, (int) $request->integer('per_page', 15));

        return response()->json(ProductResource::collection($products)->response()->getData(true));
    }

    #[OA\Get(
        path: '/catalog/products/{slug}',
        tags: ['Catalog'],
        summary: 'View a single product by slug',
        parameters: [new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Product detail'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function show(string $slug): JsonResponse
    {
        $product = $this->products->findBySlug($slug);

        abort_if(! $product || ! $product->is_active, 404, 'Product not found.');

        $product->load(['category', 'brand', 'variants', 'images', 'stocks']);
        $product->loadAvg('approvedReviews as reviews_avg_rating', 'rating');
        $product->loadCount('approvedReviews as reviews_count');

        return response()->json(['data' => new ProductResource($product)]);
    }
}
