<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Http\Resources\ProductReviewResource;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class ReviewController extends Controller
{
    #[OA\Get(
        path: '/catalog/products/{product}/reviews',
        tags: ['Catalog'],
        summary: 'List a product\'s approved reviews',
        parameters: [new OA\Parameter(name: 'product', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Paginated approved reviews')],
    )]
    public function index(Request $request, Product $product): JsonResponse
    {
        $reviews = $product->approvedReviews()
            ->with(['user', 'images'])
            ->latest()
            ->paginate((int) $request->integer('per_page', 10));

        return response()->json(ProductReviewResource::collection($reviews)->response()->getData(true));
    }

    #[OA\Post(
        path: '/catalog/products/{product}/reviews',
        tags: ['Catalog'],
        summary: 'Submit (or update) your review for a product — goes to pending moderation',
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'product', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 201, description: 'Review submitted, pending approval'),
            new OA\Response(response: 401, description: 'Not authenticated'),
        ],
    )]
    public function store(StoreReviewRequest $request, Product $product): JsonResponse
    {
        // One review per customer per product: resubmitting updates their
        // existing review and sends it back into the moderation queue,
        // rather than creating duplicates.
        $review = ProductReview::query()->updateOrCreate(
            ['product_id' => $product->id, 'user_id' => $request->user()->id],
            [
                'rating' => $request->validated('rating'),
                'title' => $request->validated('title'),
                'comment' => $request->validated('comment'),
                'status' => 'pending',
                'moderated_by' => null,
                'moderated_at' => null,
            ]
        );

        // Only touch photos if new ones were submitted with this request —
        // resubmitting text/rating alone shouldn't wipe existing photos.
        if ($request->hasFile('images')) {
            foreach ($review->images as $existingImage) {
                Storage::disk('public')->delete($existingImage->path);
                $existingImage->delete();
            }

            foreach ($request->file('images', []) as $index => $image) {
                $review->images()->create([
                    'path' => $image->store('reviews', 'public'),
                    'sort_order' => $index,
                ]);
            }
        }

        return response()->json([
            'data' => new ProductReviewResource($review->load(['user', 'images'])),
            'message' => 'Thanks for your review! It will appear once approved by our team.',
        ], 201);
    }

    #[OA\Get(
        path: '/catalog/products/{product}/reviews/mine',
        tags: ['Catalog'],
        summary: "Get the current user's own review for this product, if any",
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'product', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'The user\'s review, or null')],
    )]
    public function mine(Request $request, Product $product): JsonResponse
    {
        $review = ProductReview::query()
            ->where('product_id', $product->id)
            ->where('user_id', $request->user()->id)
            ->with('images')
            ->first();

        return response()->json(['data' => $review ? new ProductReviewResource($review) : null]);
    }
}
