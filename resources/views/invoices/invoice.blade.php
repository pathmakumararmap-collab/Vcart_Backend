<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoiceNo }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; font-size: 11px; }
        th { background: #f3f4f6; }
        .totals td { border: none; }
        .totals { width: 40%; margin-left: 60%; margin-top: 12px; }
        .text-right { text-align: right; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Vcart E-commerce</h1>
            <div class="muted">Colombo, Sri Lanka</div>
        </div>
        <div class="text-right">
            <h1>Invoice {{ $invoiceNo }}</h1>
            <div class="muted">Order #{{ $order->order_no }}</div>
            <div class="muted">Issued: {{ $issuedAt->format('Y-m-d H:i') }}</div>
        </div>
    </div>

    <div>
        <strong>Billed to:</strong><br>
        {{ $order->customer_name }}<br>
        {{ $order->customer_phone }}<br>
        {{ $order->customer_email }}
        @if($order->shippingAddress)
            <br>{{ $order->shippingAddress->address_line1 }}, {{ $order->shippingAddress->city }}
        @endif
    </div>

    <table>
        <thead>
        <tr>
            <th>Product</th>
            <th>SKU</th>
            <th class="text-right">Qty</th>
            <th class="text-right">Unit Price</th>
            <th class="text-right">Discount</th>
            <th class="text-right">Tax</th>
            <th class="text-right">Subtotal</th>
        </tr>
        </thead>
        <tbody>
        @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->sku }}</td>
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right">{{ number_format($item->discount, 2) }}</td>
                <td class="text-right">{{ number_format($item->tax, 2) }}</td>
                <td class="text-right">{{ number_format($item->subtotal, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="text-right">{{ $order->currency }} {{ number_format($order->subtotal, 2) }}</td></tr>
        <tr><td>Discount</td><td class="text-right">-{{ number_format($order->discount_amount, 2) }}</td></tr>
        <tr><td>Tax</td><td class="text-right">{{ number_format($order->tax_amount, 2) }}</td></tr>
        <tr><td>Shipping</td><td class="text-right">{{ number_format($order->shipping_amount, 2) }}</td></tr>
        <tr><td><strong>Total</strong></td><td class="text-right"><strong>{{ $order->currency }} {{ number_format($order->total_amount, 2) }}</strong></td></tr>
    </table>

    <p class="muted" style="margin-top: 40px;">Thank you for shopping with Vcart E-commerce.</p>
</body>
</html>
