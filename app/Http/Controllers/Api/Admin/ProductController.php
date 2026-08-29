<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\BarcodeService;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly BarcodeService $barcodes,
        private readonly InventoryService $inventory,
    ) {}

    #[OA\Get(
        path: '/admin/products',
        tags: ['Admin Products'],
        summary: 'List / search products (admin)',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Paginated product list')],
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $filters = $request->only(['keyword', 'category_id', 'brand_id', 'is_active', 'is_featured', 'sort']);
        $products = $this->products->search($filters, (int) $request->integer('per_page', 15));

        return response()->json(ProductResource::collection($products)->response()->getData(true));
    }

    #[OA\Post(
        path: '/admin/products',
        tags: ['Admin Products'],
        summary: 'Create a product (with optional variants and images)',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 201, description: 'Created')],
    )]
    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['variants', 'images', 'warehouse_id', 'initial_quantity']);
        $data['slug'] = Str::slug($request->string('name')).'-'.Str::lower(Str::random(6));
        $data['barcode'] = $data['barcode'] ?? $this->barcodes->generateUniqueCode();
        $data['created_by'] = $request->user()->id;

        $product = Product::query()->create($data);

        $warehouse = $request->filled('warehouse_id')
            ? Warehouse::query()->find($request->integer('warehouse_id'))
            : Warehouse::query()->orderBy('id')->first();

        $createdVariants = [];
        foreach ($request->input('variants', []) as $index => $variant) {
            if ($request->hasFile("variants.{$index}.image")) {
                $variant['image'] = $request->file("variants.{$index}.image")->store('products/variants', 'public');
            }
            $initialQuantity = (int) ($variant['initial_quantity'] ?? 0);
            unset($variant['initial_quantity']);

            $createdVariant = $product->variants()->create($variant);
            $createdVariants[] = ['variant' => $createdVariant, 'quantity' => $initialQuantity];
        }

        foreach ($request->file('images', []) as $index => $image) {
            $product->images()->create([
                'path' => $image->store('products', 'public'),
                'is_primary' => $index === 0,
                'sort_order' => $index,
            ]);
        }

        if ($warehouse) {
            if ($createdVariants) {
                foreach ($createdVariants as $entry) {
                    if ($entry['quantity'] > 0) {
                        $this->inventory->add(
                            product: $product,
                            variant: $entry['variant'],
                            warehouse: $warehouse,
                            quantity: $entry['quantity'],
                            type: 'initial',
                            note: 'Starting stock set when the product was created.',
                            userId: $request->user()->id,
                        );
                    }
                }
            } elseif ($request->integer('initial_quantity') > 0) {
                $this->inventory->add(
                    product: $product,
                    variant: null,
                    warehouse: $warehouse,
                    quantity: $request->integer('initial_quantity'),
                    type: 'initial',
                    note: 'Starting stock set when the product was created.',
                    userId: $request->user()->id,
                );
            }
        }

        return response()->json(['data' => new ProductResource($product->fresh(['category', 'brand', 'variants', 'images']))], 201);
    }

    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        $product->load(['category', 'brand', 'supplier', 'variants', 'images', 'stocks.warehouse']);

        return response()->json(['data' => new ProductResource($product)]);
    }

    #[OA\Put(
        path: '/admin/products/{product}',
        tags: ['Admin Products'],
        summary: 'Update a product',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Updated')],
    )]
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $product->update($request->safe()->except(['variants', 'images']));

        foreach ($request->input('variants', []) as $index => $variantInput) {
            if ($request->hasFile("variants.{$index}.image")) {
                $variantInput['image'] = $request->file("variants.{$index}.image")->store('products/variants', 'public');
            }

            $variantId = $variantInput['id'] ?? null;
            unset($variantInput['id']);

            if ($variantId) {
                $product->variants()->whereKey($variantId)->update($variantInput);
            } else {
                $product->variants()->create($variantInput);
            }
        }

        $existingImageCount = $product->images()->count();
        foreach ($request->file('images', []) as $offset => $image) {
            $product->images()->create([
                'path' => $image->store('products', 'public'),
                'is_primary' => $existingImageCount === 0 && $offset === 0,
                'sort_order' => $existingImageCount + $offset,
            ]);
        }

        return response()->json(['data' => new ProductResource($product->fresh(['category', 'brand', 'variants', 'images']))]);
    }

    #[OA\Delete(
        path: '/admin/products/{product}/images/{image}',
        tags: ['Admin Products'],
        summary: 'Remove a single product image',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Deleted')],
    )]
    public function destroyImage(Product $product, ProductImage $image): JsonResponse
    {
        $this->authorize('update', $product);
        abort_unless($image->product_id === $product->id, 404);

        if ($image->path) {
            Storage::disk('public')->delete($image->path);
        }

        $wasPrimary = (bool) $image->is_primary;
        $image->delete();

        if ($wasPrimary) {
            $product->images()->orderBy('sort_order')->first()?->update(['is_primary' => true]);
        }

        return response()->json(['data' => new ProductResource($product->fresh(['category', 'brand', 'variants', 'images']))]);
    }

    #[OA\Delete(
        path: '/admin/products/{product}/variants/{variant}',
        tags: ['Admin Products'],
        summary: 'Remove a single product variant',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Deleted')],
    )]
    public function destroyVariant(Product $product, ProductVariant $variant): JsonResponse
    {
        $this->authorize('update', $product);
        abort_unless($variant->product_id === $product->id, 404);

        if ($variant->image) {
            Storage::disk('public')->delete($variant->image);
        }

        $variant->delete();

        return response()->json(['data' => new ProductResource($product->fresh(['category', 'brand', 'variants', 'images']))]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return response()->json(['message' => 'Product deleted.']);
    }

    #[OA\Get(
        path: '/admin/products/{product}/barcode',
        tags: ['Admin Products'],
        summary: 'Generate a scannable barcode image (SVG) for a product',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'SVG barcode image')],
    )]
    public function barcode(Product $product): Response
    {
        $this->authorize('view', $product);

        return response($this->barcodes->generateForProduct($product, 'svg'), 200, [
            'Content-Type' => 'image/svg+xml',
        ]);
    }
}
