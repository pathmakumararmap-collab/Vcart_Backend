<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceService
{
    public function generate(Order $order): Invoice
    {
        $order->loadMissing(['items', 'shippingAddress']);

        $invoiceNo = 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        $issuedAt = now();

        $pdf = Pdf::loadView('invoices.invoice', [
            'order' => $order,
            'invoiceNo' => $invoiceNo,
            'issuedAt' => $issuedAt,
        ]);

        $path = "invoices/{$invoiceNo}.pdf";
        Storage::disk('local')->put($path, $pdf->output());

        return Invoice::query()->create([
            'invoice_no' => $invoiceNo,
            'order_id' => $order->id,
            'path' => $path,
            'issued_at' => $issuedAt,
            'total_amount' => $order->total_amount,
            'status' => $order->payment_status === 'paid' ? 'paid' : 'issued',
        ]);
    }

        public function download(Invoice $invoice): StreamedResponse
    {
        if (! Storage::disk('local')->exists($invoice->path)) {
            $this->regenerateFile($invoice);
        }

        return Storage::disk('local')->download($invoice->path, "{$invoice->invoice_no}.pdf");
    }

    private function regenerateFile(Invoice $invoice): void
    {
        $order = $invoice->order()->with(['items', 'shippingAddress'])->firstOrFail();

        $pdf = Pdf::loadView('invoices.invoice', [
            'order' => $order,
            'invoiceNo' => $invoice->invoice_no,
            'issuedAt' => $invoice->issued_at,
        ]);

        Storage::disk('local')->put($invoice->path, $pdf->output());
    }
}
