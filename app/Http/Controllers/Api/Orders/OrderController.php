<?php

namespace App\Http\Controllers\Api\Orders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CancelOrderRequest;
use App\Http\Requests\Order\StoreFacebookOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\InvoiceService;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly OrderService $orderService,
        private readonly PaymentService $payments,
        private readonly InvoiceService $invoices,
    ) {}

    #[OA\Get(
        path: '/admin/orders',
        tags: ['Admin Orders'],
        summary: 'List/search orders across all channels (website, facebook, pos)',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'source', in: 'query', schema: new OA\Schema(type: 'string', enum: ['website', 'facebook', 'pos'])),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'date_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'date_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated order list')],
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $filters = $request->only(['source', 'status', 'payment_status', 'warehouse_id', 'user_id', 'date_from', 'date_to', 'keyword']);
        $orders = $this->orders->search($filters, (int) $request->integer('per_page', 15));

        return response()->json(OrderResource::collection($orders)->response()->getData(true));
    }

    #[OA\Post(
        path: '/admin/orders/facebook',
        tags: ['Admin Orders'],
        summary: 'Import an order received via Facebook / Messenger commerce',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 201, description: 'Order created and inventory deducted')],
    )]
    public function storeFacebookOrder(StoreFacebookOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->placeOrder([
            ...$request->validated(),
            'source' => 'facebook',
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['data' => new OrderResource($order->fresh(['items', 'warehouse']))], 201);
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return response()->json(['data' => new OrderResource($order->load(['items', 'warehouse', 'user', 'shippingAddress', 'billingAddress', 'payments.paymentMethod', 'statusHistories', 'invoice']))]);
    }

    #[OA\Put(
        path: '/admin/orders/{order}/status',
        tags: ['Admin Orders'],
        summary: 'Update an order status',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Updated')],
    )]
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        $order = $this->orderService->updateStatus($order, $request->string('status'), $request->input('note'), $request->user()->id);

        return response()->json(['data' => new OrderResource($order)]);
    }

    public function cancel(CancelOrderRequest $request, Order $order): JsonResponse
    {
        $this->authorize('cancel', $order);

        $order = $this->orderService->cancelOrder($order, $request->string('reason'), $request->user()->id);

        return response()->json(['data' => new OrderResource($order)]);
    }

    public function recordPayment(StorePaymentRequest $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        $payment = $this->payments->recordPayment(
            order: $order,
            paymentMethodId: $request->integer('payment_method_id'),
            amount: (float) $request->input('amount'),
            transactionId: $request->input('transaction_id'),
            userId: $request->user()->id,
        );

        return response()->json(['data' => new PaymentResource($payment)], 201);
    }

    public function generateInvoice(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $invoice = $this->invoices->generate($order->fresh());

        return response()->json(['data' => new InvoiceResource($invoice)], 201);
    }
}