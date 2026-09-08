<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CancelOrderRequest;
use App\Http\Requests\Order\PlaceOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\CartService;
use App\Services\InvoiceService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly CartService $carts,
        private readonly InvoiceService $invoices,
    ) {}

    #[OA\Get(
        path: '/customer/orders',
        tags: ['Customer Orders'],
        summary: "List the authenticated customer's orders",
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Order list')],
    )]
    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()->orders()
            ->with(['items', 'warehouse'])
            ->latest()
            ->paginate((int) $request->integer('per_page', 15));

        return response()->json(OrderResource::collection($orders)->response()->getData(true));
    }

    #[OA\Post(
        path: '/customer/checkout',
        tags: ['Customer Orders'],
        summary: 'Place a website order from the current cart / provided items',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Order placed'),
            new OA\Response(response: 422, description: 'Insufficient stock or invalid coupon'),
        ],
    )]
    public function checkout(PlaceOrderRequest $request): JsonResponse
    {
        $user = $request->user();

        $order = $this->orders->placeOrder([
            ...$request->validated(),
            'source' => 'website',
            'user_id' => $user?->id,
            'created_by' => $user?->id,
        ]);

        if ($user) {
            $cart = $this->carts->getOrCreateCart($user, null);
            $this->carts->clear($cart);
        }

        $this->invoices->generate($order->fresh());

        return response()->json(['data' => new OrderResource($order->fresh(['items', 'warehouse', 'invoice']))], 201);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return response()->json(['data' => new OrderResource($order->load(['items', 'warehouse', 'payments.paymentMethod', 'statusHistories', 'invoice']))]);
    }

    public function cancel(CancelOrderRequest $request, Order $order): JsonResponse
    {
        $this->authorize('cancel', $order);

        $order = $this->orders->cancelOrder($order, $request->string('reason'), $request->user()->id);

        return response()->json(['data' => new OrderResource($order)]);
    }
}