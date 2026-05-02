@php
    $currencySymbol = $settings['currency_symbol'] ?? '৳';
    $currencyPosition = $settings['currency_position'] ?? 'before';

    $money = function ($amount) use ($currencySymbol, $currencyPosition) {
        $formatted = number_format((float) $amount, 2);
        return $currencyPosition === 'after' ? $formatted . $currencySymbol : $currencySymbol . $formatted;
    };

    $currentDue = (float) $invoice->previous_due + (float) $invoice->total - (float) $invoice->paid_amount;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>

    <style>
        @page {
            margin: 18px 18px 22px 18px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111;
            margin: 0;
            padding: 0;
        }

        .invoice {
            width: 100%;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo-cell {
            width: 38%;
        }

        .logo {
            max-width: 190px;
            max-height: 95px;
        }

        .company-name {
            font-size: 18px;
            font-weight: 700;
            text-align: right;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .company-info {
            font-size: 11px;
            text-align: right;
            line-height: 1.45;
        }

        .title {
            text-align: center;
            font-size: 16px;
            font-style: italic;
            font-weight: 700;
            border-top: 1px solid #333;
            border-bottom: 1px solid #333;
            padding: 3px 0;
            margin-bottom: 6px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .meta-table td {
            padding: 2px 3px;
            vertical-align: top;
        }

        .meta-label {
            width: 95px;
            font-weight: 700;
        }

        .colon {
            width: 8px;
            text-align: center;
            font-weight: 700;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #333;
            padding: 5px 6px;
        }

        .items-table th {
            text-align: center;
            font-weight: 700;
            background: #f5f5f5;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: 700;
        }

        .summary-row {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .summary-row td {
            vertical-align: top;
        }

        .amount-words {
            font-weight: 700;
            padding-top: 12px;
            width: 62%;
        }

        .totals-table {
            width: 38%;
            border-collapse: collapse;
            margin-left: auto;
        }

        .totals-table td {
            border-bottom: 1px solid #333;
            padding: 4px 6px;
        }

        .totals-table td:first-child {
            font-weight: 700;
            text-align: right;
        }

        .due-table {
            width: 45%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .due-table td {
            border: 1px solid #333;
            padding: 4px 6px;
        }

        .due-table td:first-child {
            font-weight: 700;
        }

        .policy {
            margin-top: 28px;
            font-size: 12px;
        }

        .policy-title {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .policy p {
            margin: 2px 0;
        }

        .footer {
            position: fixed;
            left: 18px;
            right: 18px;
            bottom: 18px;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .signature-table td {
            width: 50%;
            vertical-align: bottom;
            font-size: 11px;
        }

        .sign-line {
            border-top: 1px solid #333;
            width: 150px;
            padding-top: 3px;
        }

        .sign-right {
            margin-left: auto;
            text-align: center;
            width: 230px;
        }

        .thank-you {
            text-align: center;
            font-size: 11px;
            margin-top: 4px;
        }

        .developer {
            text-align: center;
            font-size: 10px;
            font-style: italic;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="invoice">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if($logoDataUri)
                        <img src="{{ $logoDataUri }}" class="logo" alt="Logo">
                    @endif
                </td>
                <td>
                    <div class="company-name">{{ $tenant->name }}</div>
                    <div class="company-info">
                        @if(!empty($settings['shop_address']))
                            {!! nl2br(e($settings['shop_address'])) !!}<br>
                        @endif

                        @if(!empty($settings['shop_email']))
                            E-mail: {{ $settings['shop_email'] }}<br>
                        @endif

                        @if(!empty($settings['shop_phone']))
                            Mobile: {{ $settings['shop_phone'] }}
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <div class="title">Sales Invoice</div>

        <table class="meta-table">
            <tr>
                <td class="meta-label">Invoice No.</td>
                <td class="colon">:</td>
                <td class="bold">{{ $invoice->invoice_number }}</td>

                <td class="meta-label">Store Location</td>
                <td class="colon">:</td>
                <td class="bold">{{ $invoice->warehouse->name }}</td>
            </tr>
            <tr>
                <td class="meta-label">Sold to</td>
                <td class="colon">:</td>
                <td class="bold">{{ $invoice->customer->name }}</td>

                <td class="meta-label">Date</td>
                <td class="colon">:</td>
                <td class="bold">{{ $invoice->invoice_date->format('M d, Y') }}</td>
            </tr>
            <tr>
                <td class="meta-label">Address</td>
                <td class="colon">:</td>
                <td>{{ $invoice->customer->address ?: '-' }}</td>

                <td class="meta-label">Sales Person</td>
                <td class="colon">:</td>
                <td>{{ $invoice->creator?->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Phone No</td>
                <td class="colon">:</td>
                <td>{{ $invoice->customer->phone }}</td>

                <td class="meta-label">Entry By</td>
                <td class="colon">:</td>
                <td>{{ $invoice->creator?->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Remarks</td>
                <td class="colon">:</td>
                <td colspan="4">{{ $invoice->notes ?: '' }}</td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 7%;">Sl. No.</th>
                    <th style="width: 49%;">Product Description</th>
                    <th style="width: 12%;">Quantity</th>
                    <th style="width: 16%;">Unit Price</th>
                    <th style="width: 16%;">Total Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                    <tr>
                        <td class="center bold">{{ $loop->iteration }}</td>
                        <td>
                            <div class="bold">{{ $item->description }}</div>
                            <div>SKU: {{ $item->product->sku }}</div>
                            @if((int) $item->product->warranty_duration_months > 0)
                                <div>Warranty: {{ (int) $item->product->warranty_duration_months }} Months</div>
                            @endif
                        </td>
                        <td class="center bold">{{ $item->quantity }}</td>
                        <td class="right bold">{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="right bold">{{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="summary-row">
            <tr>
                <td class="amount-words">
                    IN WORD: {{ $amountInWords }}
                </td>
                <td>
                    <table class="totals-table">
                        <tr>
                            <td>Total Amount</td>
                            <td class="right bold">{{ number_format((float) $invoice->subtotal, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Discount</td>
                            <td class="right bold">{{ number_format((float) $invoice->discount_amount, 2) }}</td>
                        </tr>
                        @if((float) $invoice->tax_amount > 0)
                            <tr>
                                <td>Tax {{ number_format((float) $invoice->tax_percent, 2) }}%</td>
                                <td class="right bold">{{ number_format((float) $invoice->tax_amount, 2) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td>Total Amount</td>
                            <td class="right bold">{{ number_format((float) $invoice->total, 2) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="due-table">
            <tr>
                <td>Previous Due</td>
                <td class="right bold">{{ number_format((float) $invoice->previous_due, 2) }}</td>
            </tr>
            <tr>
                <td>Sale Amount</td>
                <td class="right bold">{{ number_format((float) $invoice->total, 2) }}</td>
            </tr>
            <tr>
                <td>Collection</td>
                <td class="right bold">{{ number_format((float) $invoice->paid_amount, 2) }}</td>
            </tr>
            <tr>
                <td>Current Due</td>
                <td class="right bold">{{ number_format($currentDue, 2) }}</td>
            </tr>
        </table>

        <div class="policy">
            <div class="policy-title">Warranty Policy-</div>
            <p>* Warranty will be void if there is any physical damage, burn issue or liquid damage.</p>
            <p>* Warranty will be void if the product or warranty sticker is removed. Sold goods are not refundable.</p>
            <p>* Please keep the box and cash memo for warranty purpose.</p>
        </div>

        <div class="footer">
            <table class="signature-table">
                <tr>
                    <td>
                        <div class="sign-line">Customer Signature</div>
                    </td>
                    <td>
                        <div class="sign-right">
                            <div style="border-top: 1px solid #333; padding-top: 3px;">
                                Authorized Signature & Company Stamp
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="thank-you">
                {{ $settings['invoice_footer'] ?? 'Thank you for shopping with us.' }}
            </div>

            <div class="developer">
                Software Developed & Maintained by <strong>NexproBD</strong>
            </div>
        </div>
    </div>
</body>
</html>
