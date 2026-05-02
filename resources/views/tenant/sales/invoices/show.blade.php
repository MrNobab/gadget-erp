@extends('layouts.tenant')

@section('content')
    @php
        $settings = $settings ?? ($tenant->settings ?? []);
        $currencySymbol = $settings['currency_symbol'] ?? '৳';
        $currencyPosition = $settings['currency_position'] ?? 'before';
        $logoPath = $settings['logo_path'] ?? null;

        $money = function ($amount) use ($currencySymbol, $currencyPosition) {
            $formatted = number_format((float) $amount, 2);
            return $currencyPosition === 'after' ? $formatted . $currencySymbol : $currencySymbol . $formatted;
        };
    @endphp

    <style>
        @media print {
            header, nav, .no-print {
                display: none !important;
            }

            body {
                background: white !important;
            }

            main {
                max-width: none !important;
                padding: 0 !important;
            }

            .print-card {
                box-shadow: none !important;
                border: none !important;
            }
        }
    </style>

    <div class="no-print flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">Invoice {{ $invoice->invoice_number }}</h2>
            <p class="text-slate-500">View invoice, payments, and profit snapshot.</p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('tenant.invoices.pdf', [$tenant, $invoice->id]) }}" target="_blank" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Download PDF
            </a>

            <button onclick="window.print()" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
                Print
            </button>

            <a href="{{ route('tenant.invoices.index', $tenant) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
                Back
            </a>
        </div>
    </div>

    <div class="print-card bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-6">
            <div class="flex items-start gap-4">
                @if($logoPath)
                    <img src="{{ route('tenant.settings.shop.logo', $tenant) }}?v={{ $tenant->updated_at?->timestamp ?? time() }}" alt="Shop Logo" class="max-h-20 max-w-32">
                @endif

                <div>
                    <h1 class="text-2xl font-bold">{{ $tenant->name }}</h1>

                    @if(!empty($settings['shop_address']))
                        <p class="text-sm text-slate-600 whitespace-pre-line">{{ $settings['shop_address'] }}</p>
                    @endif

                    <div class="text-sm text-slate-500">
                        @if(!empty($settings['shop_phone']))
                            Phone: {{ $settings['shop_phone'] }}
                        @endif

                        @if(!empty($settings['shop_email']))
                            <br>Email: {{ $settings['shop_email'] }}
                        @endif
                    </div>
                </div>
            </div>

            <div class="text-right text-sm">
                <div class="text-slate-500">Invoice No</div>
                <div class="font-bold text-lg">{{ $invoice->invoice_number }}</div>
                <div class="mt-2 text-slate-500">Date</div>
                <div class="font-semibold">{{ $invoice->invoice_date->format('d M Y') }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 py-6 border-b border-slate-200">
            <div>
                <h3 class="font-bold mb-2">Customer</h3>
                <div class="text-sm">
                    <div class="font-semibold">{{ $invoice->customer->name }}</div>
                    <div>{{ $invoice->customer->phone }}</div>
                    <div>{{ $invoice->customer->email ?: '' }}</div>
                    <div class="whitespace-pre-line">{{ $invoice->customer->address ?: '' }}</div>
                </div>
            </div>

            <div>
                <h3 class="font-bold mb-2">Invoice Info</h3>
                <div class="text-sm space-y-1">
                    <div><strong>Warehouse:</strong> {{ $invoice->warehouse->name }}</div>
                    <div><strong>Status:</strong> {{ ucfirst($invoice->status) }}</div>
                    <div><strong>Payment:</strong> {{ ucfirst($invoice->payment_status) }}</div>
                    <div><strong>Created By:</strong> {{ $invoice->creator?->name ?? '-' }}</div>
                </div>
            </div>
        </div>

        <div class="py-6">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">Product</th>
                        <th class="text-right px-4 py-3">Qty</th>
                        <th class="text-right px-4 py-3">Unit Price</th>
                        <th class="text-right px-4 py-3 no-print">Cost Snapshot</th>
                        <th class="text-right px-4 py-3">Line Total</th>
                        <th class="text-right px-4 py-3 no-print">Gross Profit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($invoice->items as $item)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-semibold">{{ $item->description }}</div>
                                <div class="text-xs text-slate-500">{{ $item->product->sku }}</div>
                            </td>
                            <td class="px-4 py-3 text-right">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 text-right">{{ $money($item->unit_price) }}</td>
                            <td class="px-4 py-3 text-right no-print">{{ $money($item->cost_price) }}</td>
                            <td class="px-4 py-3 text-right">{{ $money($item->line_total) }}</td>
                            <td class="px-4 py-3 text-right no-print">{{ $money($item->gross_profit) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex justify-end border-t border-slate-200 pt-6">
            <div class="w-full md:w-96 text-sm space-y-2">
                <div class="flex justify-between">
                    <span>Subtotal</span>
                    <strong>{{ $money($invoice->subtotal) }}</strong>
                </div>

                <div class="flex justify-between">
                    <span>Discount</span>
                    <strong>{{ $money($invoice->discount_amount) }}</strong>
                </div>

                <div class="flex justify-between">
                    <span>Tax {{ number_format((float) $invoice->tax_percent, 2) }}%</span>
                    <strong>{{ $money($invoice->tax_amount) }}</strong>
                </div>

                <div class="flex justify-between text-lg border-t border-slate-200 pt-2">
                    <span>Total</span>
                    <strong>{{ $money($invoice->total) }}</strong>
                </div>

                <div class="flex justify-between">
                    <span>Paid</span>
                    <strong>{{ $money($invoice->paid_amount) }}</strong>
                </div>

                <div class="flex justify-between">
                    <span>Due</span>
                    <strong class="{{ (float) $invoice->due_amount > 0 ? 'text-red-600' : '' }}">
                        {{ $money($invoice->due_amount) }}
                    </strong>
                </div>

                <div class="no-print flex justify-between border-t border-slate-200 pt-2">
                    <span>Gross Profit</span>
                    <strong>{{ $money($invoice->items->sum('gross_profit')) }}</strong>
                </div>
            </div>
        </div>

        @if($invoice->notes)
            <div class="mt-6 border-t border-slate-200 pt-4 text-sm">
                <strong>Notes:</strong>
                <div class="whitespace-pre-line">{{ $invoice->notes }}</div>
            </div>
        @endif

        @if(!empty($settings['invoice_footer']))
            <div class="mt-6 border-t border-slate-200 pt-4 text-center text-sm text-slate-500">
                {{ $settings['invoice_footer'] }}
            </div>
        @endif
    </div>

    <div class="no-print grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <h3 class="text-lg font-bold mb-4">Payments</h3>

            <div class="space-y-3">
                @forelse($invoice->payments as $payment)
                    <div class="rounded-lg bg-slate-50 border border-slate-200 p-4 text-sm">
                        <div class="flex justify-between">
                            <strong>{{ $money($payment->amount) }}</strong>
                            <span>{{ $payment->paid_at->format('d M Y') }}</span>
                        </div>
                        <div class="text-slate-500">
                            {{ ucfirst(str_replace('_', ' ', $payment->method)) }}
                            @if($payment->reference)
                                — {{ $payment->reference }}
                            @endif
                        </div>
                        <div class="text-xs text-slate-500">
                            By {{ $payment->creator?->name ?? '-' }}
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No payments recorded.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <h3 class="text-lg font-bold mb-4">Record Payment</h3>

            @if((float) $invoice->due_amount <= 0)
                <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-green-700 text-sm">
                    This invoice is fully paid.
                </div>
            @else
                <form method="POST" action="{{ route('tenant.invoices.payments.store', [$tenant, $invoice->id]) }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Amount</label>
                        <input type="number" step="0.01" min="0.01" max="{{ $invoice->due_amount }}" name="amount" value="{{ old('amount', $invoice->due_amount) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Method</label>
                        <select name="method" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="cash">Cash</option>
                            <option value="bank">Bank</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="card">Card</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Reference</label>
                        <input type="text" name="reference" value="{{ old('reference') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Payment Date</label>
                        <input type="date" name="paid_at" value="{{ old('paid_at', now()->format('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Notes</label>
                        <textarea name="notes" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('notes') }}</textarea>
                    </div>

                    <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                        Save Payment
                    </button>
                </form>
            @endif
        </div>
    </div>
@endsection
