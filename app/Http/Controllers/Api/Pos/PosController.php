<?php

namespace App\Http\Controllers\Api\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\PosCheckoutRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\ProductResource;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\PosService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class PosController extends Controller
{
    public function __construct(
        private readonly PosService $pos,
        private readonly ProductRepositoryInterface $products,
    ) {}

    #[OA\Post(
        path: '/pos/checkout',
        tags: ['POS'],
        summary: 'Complete an outlet POS sale (deducts the same central inventory as website/Facebook orders)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['warehouse_id', 'items', 'payment_method_id'],
            properties: [
                new OA\Property(property: 'warehouse_id', type: 'integer'),
                new OA\Property(property: 'items', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'product_id', type: 'integer'),
                        new OA\Property(property: 'product_variant_id', type: 'integer', nullable: true),
                        new OA\Property(property: 'quantity', type: 'integer'),
                    ],
                    type: 'object',
                )),
                new OA\Property(property: 'payment_method_id', type: 'integer'),
                new OA\Property(property: 'amount_tendered', type: 'number'),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Sale completed, invoice generated'),
            new OA\Response(response: 422, description: 'Insufficient stock'),
        ],
    )]
    public function checkout(PosCheckoutRequest $request): JsonResponse
    {
        $result = $this->pos->checkout($request->validated(), $request->user()->id);

        return response()->json([
            'data' => new OrderResource($result['order']),
            'invoice' => new InvoiceResource($result['invoice']),
            'change_due' => $result['change_due'],
        ]);
    }

    #[OA\Get(
        path: '/pos/lookup/{barcode}',
        tags: ['POS'],
        summary: 'Look up a product by barcode for the POS scanner',
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'barcode', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Product found'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function lookup(string $barcode): JsonResponse
    {
        $product = $this->products->findByBarcode($barcode);

        abort_if(! $product, 404, 'Product not found for this barcode.');

        $product->load(['category', 'brand', 'stocks']);

        return response()->json(['data' => new ProductResource($product)]);
    }
}
