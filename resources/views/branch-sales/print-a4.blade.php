<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nota {{ $sale->sale_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #1f2937; margin: 24px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 16px; }
        .brand { font-size: 22px; font-weight: 700; color: #b91c1c; }
        .meta { font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; font-size: 13px; }
        th { background: #fee2e2; color: #991b1b; text-align: left; }
        .text-right { text-align: right; }
        .summary { width: 320px; margin-left: auto; margin-top: 16px; }
        .summary td { border: 0; padding: 4px 0; }
        .total { font-size: 16px; font-weight: 700; color: #991b1b; }
    </style>
</head>
<body onload="window.print()">
<div class="header">
    <div>
        <div class="brand">{{ config('app.name') }}</div>
        <div class="meta">{{ $sale->branch?->name }}</div>
        <div class="meta">{{ $sale->branch?->address }}</div>
    </div>
    <div class="meta">
        <div><strong>NO NOTA:</strong> {{ $sale->sale_number }}</div>
        <div><strong>TANGGAL:</strong> {{ $sale->sale_date?->format('d M Y H:i') }}</div>
        <div><strong>KASIR:</strong> {{ $sale->creator?->name }}</div>
        <div><strong>METODE:</strong> {{ $sale->payment_method?->value }}</div>
    </div>
</div>

<table>
    <thead>
    <tr>
        <th>No</th>
        <th>Item</th>
        <th class="text-right">Qty</th>
        <th class="text-right">Harga</th>
        <th class="text-right">Total</th>
    </tr>
    </thead>
    <tbody>
    @foreach($sale->items as $index => $line)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $line->item?->name }}</td>
            <td class="text-right">{{ \App\Support\IndoNumber::decimal($line->qty) }}</td>
            <td class="text-right">{{ \App\Support\IndoNumber::rupiah($line->unit_price) }}</td>
            <td class="text-right">{{ \App\Support\IndoNumber::rupiah($line->line_total) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="summary">
    <tr><td>Subtotal</td><td class="text-right">{{ \App\Support\IndoNumber::rupiah($sale->subtotal) }}</td></tr>
    <tr><td>Diskon</td><td class="text-right">{{ \App\Support\IndoNumber::rupiah($sale->discount_amount) }}</td></tr>
    <tr><td>Pajak</td><td class="text-right">{{ \App\Support\IndoNumber::rupiah($sale->tax_amount) }}</td></tr>
    <tr class="total"><td>Total</td><td class="text-right">{{ \App\Support\IndoNumber::rupiah($sale->total_amount) }}</td></tr>
</table>
</body>
</html>
