<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoices) {}

    public function download(Request $request, Invoice $invoice): StreamedResponse
    {
        $order = $invoice->order;
        $user = $request->user();

        abort_unless(
            $user && ($user->can('orders.view') || $order->user_id === $user->id),
            403,
        );

        return $this->invoices->download($invoice);
    }
}
