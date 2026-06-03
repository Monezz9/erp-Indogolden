@php
    use App\Support\IndoNumber;
    use App\Enums\PaymentMethod;
    use Illuminate\Support\Facades\Storage;

    $paperSize = $setting->paper_size ?: '80mm';
    $isA4 = $paperSize === 'A4';
    $storeName = $setting->store_name ?: config('app.name', 'INDOGOLDEN ERP');
    $storeAddress = $setting->store_address ?: ($sale->branch?->address ?? '');
    $storePhone = $setting->store_phone ?: ($sale->branch?->phone ?? '');
    $footerText = $setting->footer_text ?: 'Terima kasih';
    $logoUrl = $setting->logo_path
        ? ($setting->logo_path === 'images/logo-indogolden.png' ? asset($setting->logo_path) : Storage::disk('public')->url($setting->logo_path))
        : null;
    $qrisUrl = $setting->qris_image_path ? Storage::disk('public')->url($setting->qris_image_path) : null;
    $cashierName = $sale->cashier?->name ?? $sale->creator?->name ?? '-';
    $paymentLabel = PaymentMethod::options()[$sale->payment_method?->value] ?? ($sale->payment_method?->value ?? '-');
@endphp

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nota {{ $sale->sale_number }}</title>
    <style>
        @page {
            size: {{ $isA4 ? 'A4' : $paperSize }} auto;
            margin: {{ $isA4 ? '14mm' : '0' }};
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #fff;
            color: #111;
            font-family: {{ $isA4 ? 'Arial, sans-serif' : "'Courier New', monospace" }};
            font-size: {{ $isA4 ? '12px' : '11px' }};
            line-height: 1.35;
        }
        .receipt {
            width: {{ $isA4 ? '100%' : $paperSize }};
            max-width: {{ $isA4 ? '190mm' : $paperSize }};
            margin: 0 auto;
            padding: {{ $isA4 ? '0' : '8px' }};
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .logo {
            display: block;
            max-width: {{ $isA4 ? '90px' : '34mm' }};
            max-height: {{ $isA4 ? '70px' : '22mm' }};
            margin: 0 auto 6px;
            object-fit: contain;
        }
        .store-name {
            font-size: {{ $isA4 ? '20px' : '13px' }};
            font-weight: 700;
            text-transform: uppercase;
        }
        .muted { color: #333; }
        .line {
            border-top: 1px dashed #111;
            margin: 8px 0;
        }
        .row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
        }
        .row span:last-child {
            text-align: right;
            white-space: nowrap;
        }
        .meta-grid {
            display: {{ $isA4 ? 'grid' : 'block' }};
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: {{ $isA4 ? '6px 5px' : '3px 0' }};
            vertical-align: top;
        }
        th {
            border-bottom: 1px solid #111;
            font-weight: 700;
            text-align: left;
        }
        tbody tr + tr td {
            border-top: {{ $isA4 ? '1px solid #ddd' : '0' }};
        }
        .summary {
            width: {{ $isA4 ? '300px' : '100%' }};
            margin-left: auto;
        }
        .total {
            font-size: {{ $isA4 ? '15px' : '12px' }};
            font-weight: 700;
        }
        .qris {
            display: block;
            width: {{ $isA4 ? '120px' : '34mm' }};
            max-width: 100%;
            margin: 8px auto 0;
        }
        .footer {
            margin-top: 10px;
            white-space: pre-line;
        }

        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .receipt { margin: 0; }
        }
    </style>
</head>
<body onload="window.print()">
<main class="receipt">
    <header class="center">
        @if($setting->show_logo && $logoUrl)
            <img class="logo" src="{{ $logoUrl }}" alt="Logo">
        @endif

        <div class="store-name">{{ $storeName }}</div>
        @if($storeAddress)
            <div class="muted">{{ $storeAddress }}</div>
        @endif
        @if($storePhone)
            <div class="muted">{{ $storePhone }}</div>
        @endif
    </header>

    <div class="line"></div>

    <section class="meta-grid">
        <div>
            <div>No: {{ $sale->sale_number }}</div>
            <div>Tanggal: {{ $sale->sale_date?->format('d/m/Y H:i') }}</div>
        </div>
        <div>
            <div>Cabang: {{ $sale->branch?->name ?? '-' }}</div>
            <div>Kasir: {{ $cashierName }}</div>
            <div>Bayar: {{ $paymentLabel }}</div>
        </div>
    </section>

    <div class="line"></div>

    <table>
        <thead>
        <tr>
            <th>Item</th>
            <th class="right">Qty</th>
            <th class="right">Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($sale->items as $line)
            <tr>
                <td>
                    {{ $line->item?->name ?? '-' }}
                    <div class="muted">{{ IndoNumber::rupiah($line->unit_price) }}</div>
                </td>
                <td class="right">{{ IndoNumber::decimal($line->qty) }}</td>
                <td class="right">{{ IndoNumber::rupiah($line->line_total) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="line"></div>

    <section class="summary">
        <div class="row"><span>Subtotal</span><span>{{ IndoNumber::rupiah($sale->subtotal) }}</span></div>
        @if($setting->show_discount)
            <div class="row"><span>Diskon</span><span>{{ IndoNumber::rupiah($sale->discount_amount) }}</span></div>
        @endif
        @if($setting->show_tax)
            <div class="row"><span>Pajak</span><span>{{ IndoNumber::rupiah($sale->tax_amount) }}</span></div>
        @endif
        <div class="row total"><span>Total</span><span>{{ IndoNumber::rupiah($sale->total_amount) }}</span></div>
    </section>

    @if($setting->show_qris && $qrisUrl)
        <div class="line"></div>
        <div class="center">
            <div>QRIS</div>
            <img class="qris" src="{{ $qrisUrl }}" alt="QRIS">
        </div>
    @endif

    <div class="line"></div>

    <footer class="center footer">{{ $footerText }}</footer>
</main>
</body>
</html>
