<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function recordPayment(
        Order $order,
        int $paymentMethodId,
        float $amount,
        ?string $transactionId = null,
        ?array $meta = null,
        ?int $userId = null,
        string $status = 'completed',
    ): Payment {
        return DB::transaction(function () use ($order, $paymentMethodId, $amount, $transactionId, $meta, $userId, $status) {
            PaymentMethod::query()->findOrFail($paymentMethodId);

            $payment = Payment::query()->create([
                'payment_no' => 'PAY-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'order_id' => $order->id,
                'payment_method_id' => $paymentMethodId,
                'amount' => $amount,
                'status' => $status,
                'transaction_id' => $transactionId,
                'paid_at' => $status === 'completed' ? now() : null,
                'meta' => $meta,
                'created_by' => $userId,
            ]);

            if ($status === 'completed') {
                $paidAmount = (float) $order->paid_amount + $amount;
                $order->update([
                    'paid_amount' => $paidAmount,
                    'payment_status' => $this->resolvePaymentStatus($paidAmount, (float) $order->total_amount),
                ]);
            }

            return $payment;
        });
    }

    public function refund(Payment $payment, ?float $amount = null): Payment
    {
        return DB::transaction(function () use ($payment, $amount) {
            $refundAmount = $amount ?? (float) $payment->amount;

            $payment->update(['status' => 'refunded']);

            $order = $payment->order;
            $paidAmount = max(0, (float) $order->paid_amount - $refundAmount);

            $order->update([
                'paid_amount' => $paidAmount,
                'payment_status' => $paidAmount <= 0 ? 'refunded' : 'partial',
            ]);

            return $payment;
        });
    }

    private function resolvePaymentStatus(float $paidAmount, float $totalAmount): string
    {
        if ($paidAmount <= 0) {
            return 'unpaid';
        }

        if ($paidAmount >= $totalAmount) {
            return 'paid';
        }

        return 'partial';
    }
}
