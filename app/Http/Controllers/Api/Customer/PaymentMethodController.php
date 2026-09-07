<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class PaymentMethodController extends Controller
{
    /**
     * Only these methods are offered at storefront checkout — the rest
     * (cash, bank_transfer, generic card) are for POS/manual admin use.
     */
    private const CHECKOUT_CODES = ['cod', 'online_gateway'];

    #[OA\Get(
        path: '/customer/payment-methods',
        tags: ['Customer Orders'],
        summary: 'List payment methods available at checkout (Cash on Delivery, Online Card)',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Payment method list')],
    )]
    public function index(): JsonResponse
    {
        $methods = PaymentMethod::query()
            ->where('is_active', true)
            ->whereIn('code', self::CHECKOUT_CODES)
            ->orderByRaw("field(code, 'cod', 'online_gateway')")
            ->get(['id', 'name', 'code']);

        return response()->json(['data' => $methods]);
    }
}
