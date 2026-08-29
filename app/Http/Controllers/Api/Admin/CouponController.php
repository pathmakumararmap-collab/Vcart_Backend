<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\StoreCouponRequest;
use App\Http\Requests\Coupon\UpdateCouponRequest;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use App\Repositories\Contracts\CouponRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CouponController extends Controller
{
    public function __construct(private readonly CouponRepositoryInterface $coupons) {}

    #[OA\Get(
        path: '/admin/coupons',
        tags: ['Coupons'],
        summary: 'List coupons',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Coupon list')],
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Coupon::class);

        return response()->json($this->coupons->paginate((int) $request->integer('per_page', 15))->toArray());
    }

    #[OA\Post(
        path: '/admin/coupons',
        tags: ['Coupons'],
        summary: 'Create a coupon',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 201, description: 'Created')],
    )]
    public function store(StoreCouponRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['product_ids', 'category_ids']);
        $coupon = $this->coupons->create($data);

        if ($request->filled('product_ids')) {
            $coupon->products()->sync($request->input('product_ids'));
        }

        if ($request->filled('category_ids')) {
            $coupon->categories()->sync($request->input('category_ids'));
        }

        return response()->json(['data' => new CouponResource($coupon)], 201);
    }

    public function show(Coupon $coupon): JsonResponse
    {
        $this->authorize('view', $coupon);

        return response()->json(['data' => new CouponResource($coupon->load('products', 'categories'))]);
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): JsonResponse
    {
        $data = $request->safe()->except(['product_ids', 'category_ids']);
        $this->coupons->update($coupon, $data);

        if ($request->has('product_ids')) {
            $coupon->products()->sync($request->input('product_ids'));
        }

        if ($request->has('category_ids')) {
            $coupon->categories()->sync($request->input('category_ids'));
        }

        return response()->json(['data' => new CouponResource($coupon->fresh())]);
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $this->authorize('delete', $coupon);

        $this->coupons->delete($coupon);

        return response()->json(['message' => 'Coupon deleted.']);
    }
}
