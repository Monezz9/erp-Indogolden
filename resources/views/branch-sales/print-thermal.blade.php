<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nota {{ $sale->sale_number }}</title>
    <style>
        body { font-family: 'Courier New', monospace; margin: 0; padding: 12px; }
        .wrapper { width: 78mm; }
        .center { text-align: center; }
        .line { border-top: 1px dashed #000; margin: 8px 0; }
        .row { display: flex; justify-content: space-between; gap: 8px; }
        .small { font-size: 11px; }
        .item { font-size: 12px; margin-bottom: 6px; }
        .total { font-size: 13px; font-weight: 700; }
    </style>
</head>
<body onload="window.print()">
<div class="wrapper">
    <div class="center">
        <strong>{{ config('app.name') }}</strong><br>
        <span class="small">{{ $sale->branch?->name }}</span><br>
        <span class="small">{{ $sale->branch?->address }}</span>
    </div>

    <div class="line"></div>

    <div class="small">No: {{ $sale->sale_number }}</div>
    <div class="small">Tanggal: {{ $sale->sale_date?->format('d/m/Y H:i') }}</div>
    <div class="small">Kasir: {{ $sale->creator?->name }}</div>

    <div class="line"></div>

    @foreach($sale->items as $line)
        <div class="item">
            <div>{{ $line->item?->name }}</div>
            <div class="row small">
                <span>{{ \App\Support\IndoNumber::decimal($line->qty) }} x {{ \App\Support\IndoNumber::rupiah($line->unit_price) }}</span>
                <span>{{ \App\Support\IndoNumber::rupiah($line->line_total) }}</span>
            </div>
        </div>
    @endforeach

    <div class="line"></div>

    <div class="row small"><span>Subtotal</span><span>{{ \App\Support\IndoNumber::rupiah($sale->subtotal) }}</span></div>
    <div class="row small"><span>Diskon</span><span>{{ \App\Support\IndoNumber::rupiah($sale->discount_amount) }}</span></div>
    <div class="row small"><span>Pajak</span><span>{{ \App\Support\IndoNumber::rupiah($sale->tax_amount) }}</span></div>
    <div class="row total"><span>Total</span><span>{{ \App\Support\IndoNumber::rupiah($sale->total_amount) }}</span></div>

    <div class="line"></div>

    <div class="center small">Terima kasih</div>
</div>
</body>
</html>
