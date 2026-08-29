<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductReviewResource;
use App\Models\ProductReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ReviewController extends Controller
{
    #[OA\Get(
        path: '/admin/reviews',
        tags: ['Admin Reviews'],
        summary: 'List all product reviews for moderation',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'rejected'])),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated reviews')],
    )]
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('reviews.moderate'), 403);

        $reviews = ProductReview::query()
            ->with(['user', 'images', 'product:id,name,slug'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate((int) $request->integer('per_page', 15));

        return response()->json(ProductReviewResource::collection($reviews)->response()->getData(true));
    }

    #[OA\Put(
        path: '/admin/reviews/{review}/approve',
        tags: ['Admin Reviews'],
        summary: 'Approve a pending review so it appears on the storefront',
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'review', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Approved')],
    )]
    public function approve(Request $request, ProductReview $review): JsonResponse
    {
        abort_unless($request->user()->can('reviews.moderate'), 403);

        $review->update([
            'status' => 'approved',
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        return response()->json(['data' => new ProductReviewResource($review->fresh('user'))]);
    }

    #[OA\Put(
        path: '/admin/reviews/{review}/reject',
        tags: ['Admin Reviews'],
        summary: 'Reject a review — it stays hidden from the storefront',
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'review', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Rejected')],
    )]
    public function reject(Request $request, ProductReview $review): JsonResponse
    {
        abort_unless($request->user()->can('reviews.moderate'), 403);

        $review->update([
            'status' => 'rejected',
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        return response()->json(['data' => new ProductReviewResource($review->fresh('user'))]);
    }

    #[OA\Delete(
        path: '/admin/reviews/{review}',
        tags: ['Admin Reviews'],
        summary: 'Permanently delete a review',
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'review', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Deleted')],
    )]
    public function destroy(Request $request, ProductReview $review): JsonResponse
    {
        abort_unless($request->user()->can('reviews.moderate'), 403);

        $review->delete();

        return response()->json(['message' => 'Review deleted.']);
    }
}
