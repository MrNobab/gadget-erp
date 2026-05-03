<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Barcode Labels - {{ $tenant->name }}</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #020617;
            font-family: Arial, Helvetica, sans-serif;
            background: #f8fafc;
        }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 24px;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
        }

        .toolbar h1 {
            margin: 0;
            font-size: 18px;
        }

        .toolbar p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        .toolbar a,
        .toolbar button {
            display: inline-block;
            border: 0;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .toolbar a {
            color: #334155;
            background: #f1f5f9;
        }

        .toolbar button {
            color: #ffffff;
            background: #0f172a;
        }

        .sheet {
            display: flex;
            flex-wrap: wrap;
            align-content: flex-start;
            gap: 3mm;
            padding: 8mm;
        }

        .label {
            width: 50mm;
            height: 30mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 3mm;
            overflow: hidden;
            background: #ffffff;
            border: 1px dashed #cbd5e1;
            page-break-inside: avoid;
        }

        .shop {
            font-size: 8px;
            font-weight: 700;
            line-height: 1.1;
            text-transform: uppercase;
            color: #475569;
        }

        .name {
            height: 22px;
            overflow: hidden;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.1;
        }

        .meta {
            display: flex;
            justify-content: space-between;
            gap: 4px;
            font-size: 8px;
            color: #475569;
        }

        .price {
            color: #020617;
            font-weight: 700;
        }

        .barcode {
            width: 100%;
            height: 11mm;
        }

        .barcode svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        .code {
            overflow: hidden;
            text-align: center;
            font-family: "Courier New", monospace;
            font-size: 8px;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .unsupported {
            display: grid;
            place-items: center;
            height: 11mm;
            border: 1px solid #e2e8f0;
            font-size: 8px;
            color: #dc2626;
            text-align: center;
        }

        @media print {
            @page {
                margin: 0;
            }

            body {
                background: #ffffff;
            }

            .toolbar {
                display: none;
            }

            .sheet {
                gap: 0;
                padding: 0;
            }

            .label {
                border: 0;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <h1>Barcode Labels</h1>
            <p>{{ $tenant->name }} - {{ $labels->sum('quantity') }} labels ready</p>
        </div>

        <div>
            <a href="{{ route('tenant.products.barcode-labels.index', $tenant) }}">Back</a>
            <button type="button" onclick="window.print()">Print</button>
        </div>
    </div>

    <main class="sheet">
        @foreach($labels as $label)
            @for($i = 0; $i < $label['quantity']; $i++)
                @php
                    $product = $label['product'];
                    $barcodeValue = $product->barcodeValue();
                @endphp

                <section class="label">
                    <div>
                        <div class="shop">{{ $tenant->name }}</div>
                        <div class="name">{{ $product->name }}</div>
                    </div>

                    <div class="meta">
                        <span>SKU: {{ $product->sku }}</span>
                        <span class="price">{{ nxpbd_money($product->sale_price, $tenant) }}</span>
                    </div>

                    <div>
                        <div class="barcode">
                            @if($barcodeRenderer->canEncode($barcodeValue))
                                {!! $barcodeRenderer->svg($barcodeValue) !!}
                            @else
                                <div class="unsupported">Unsupported barcode text</div>
                            @endif
                        </div>
                        <div class="code">{{ $barcodeValue }}</div>
                    </div>
                </section>
            @endfor
        @endforeach
    </main>
</body>
</html>
