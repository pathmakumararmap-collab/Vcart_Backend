<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates an outlet POS sale: places the order (which deducts from the
 * same central inventory as website/Facebook orders via OrderService +
 * InventoryService), takes payment immediately, and prints/generates the
 * invoice — matching how a physical checkout counter behaves.
 */
class PosService
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly PaymentService $payments,
        private readonly InvoiceService $invoices,
    ) {}

    /**
     * @param  array{warehouse_id: int, served_by: int, items: array<int, array{product_id: int, product_variant_id?: int|null, quantity: int}>, payment_method_id: int, customer_name?: string, customer_phone?: string, coupon_code?: string, amount_tendered?: float}  $data
     * @return array{order: Order, change_due: float}
     */
    public function checkout(array $data, int $cashierId): array
    {
        return DB::transaction(function () use ($data, $cashierId) {
            $order = $this->orders->placeOrder([
                'source' => 'pos',
                'warehouse_id' => $data['warehouse_id'],
                'items' => $data['items'],
                'coupon_code' => $data['coupon_code'] ?? null,
                'customer_name' => $data['customer_name'] ?? 'Walk-in Customer',
                'customer_phone' => $data['customer_phone'] ?? null,
                'served_by' => $cashierId,
                'created_by' => $cashierId,
                'user_id' => $data['user_id'] ?? null,
            ]);

            $amountTendered = (float) ($data['amount_tendered'] ?? $order->total_amount);
            $changeDue = max(0, round($amountTendered - (float) $order->total_amount, 2));

            $this->payments->recordPayment(
                order: $order,
                paymentMethodId: $data['payment_method_id'],
                amount: (float) $order->total_amount,
                transactionId: $data['transaction_id'] ?? null,
                meta: ['amount_tendered' => $amountTendered, 'change_due' => $changeDue],
                userId: $cashierId,
            );

            $invoice = $this->invoices->generate($order->fresh());

            return [
                'order' => $order->fresh(['items', 'payments', 'invoice']),
                'invoice' => $invoice,
                'change_due' => $changeDue,
            ];
        });
    }
}