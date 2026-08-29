<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PaymentMethodController extends Controller
{
    #[OA\Get(
        path: '/admin/payment-methods',
        tags: ['Admin Orders'],
        summary: 'List available payment methods (cash, card, bank transfer, COD, online gateway)',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Payment method list')],
    )]
    public function index(): JsonResponse
    {
        return response()->json(['data' => PaymentMethod::query()->where('is_active', true)->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('payments.create'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'in:cash,card,bank_transfer,cod,online_gateway'],
            'is_active' => ['boolean'],
            'config' => ['nullable', 'array'],
        ]);

        $method = PaymentMethod::query()->create($validated);

        return response()->json(['data' => $method], 201);
    }

    public function update(Request $request, PaymentMethod $paymentMethod): JsonResponse
    {
        abort_unless($request->user()->can('payments.update'), 403);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'config' => ['nullable', 'array'],
        ]);

        $paymentMethod->update($validated);

        return response()->json(['data' => $paymentMethod->fresh()]);
    }
}
