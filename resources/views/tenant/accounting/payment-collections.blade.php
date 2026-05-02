@extends('layouts.tenant')

@section('content')
    @php
        $symbol = $settings['currency_symbol'] ?? '৳';
        $position = $settings['currency_position'] ?? 'before';
        $money = fn ($amount) => $position === 'after'
            ? number_format((float) $amount, 2) . $symbol
            : $symbol . number_format((float) $amount, 2);

        $downloadQuery = request()->query();
    @endphp

    @include('tenant.accounting.partials.nav')

    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">Payment Collections</h2>
            <p class="text-slate-500">All collected payments with invoice, customer, collector, date, method, and reference.</p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('tenant.accounting.payment-collections.download', array_merge($downloadQuery, ['tenant' => $tenant, 'format' => 'pdf'])) }}" target="_blank" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Download PDF
            </a>

            <a href="{{ route('tenant.accounting.payment-collections.download', array_merge($downloadQuery, ['tenant' => $tenant, 'format' => 'csv'])) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
                Download CSV
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm mb-6">
        <div class="text-sm text-slate-500">Total Collection</div>
        <div class="text-3xl font-bold mt-2">{{ $money($totalCollection) }}</div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-4">
        <form method="GET" action="{{ route('tenant.accounting.payment-collections', $tenant) }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <input type="text" name="customer_search" value="{{ $customerSearch }}" placeholder="Search customer name, phone, email..." class="rounded-lg border border-slate-300 px-3 py-2">

            <select name="method" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">All Methods</option>
                <option value="cash" @selected($method === 'cash')>Cash</option>
                <option value="bank" @selected($method === 'bank')>Bank</option>
                <option value="mobile_money" @selected($method === 'mobile_money')>Mobile Money</option>
                <option value="card" @selected($method === 'card')>Card</option>
            </select>

            <input type="date" name="from" value="{{ $from }}" class="rounded-lg border border-slate-300 px-3 py-2">
            <input type="date" name="to" value="{{ $to }}" class="rounded-lg border border-slate-300 px-3 py-2">

            <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Payment Date</th>
                    <th class="text-left px-4 py-3">Recorded At</th>
                    <th class="text-left px-4 py-3">Collection No</th>
                    <th class="text-left px-4 py-3">Invoice</th>
                    <th class="text-left px-4 py-3">Customer</th>
                    <th class="text-left px-4 py-3">Method</th>
                    <th class="text-left px-4 py-3">Reference</th>
                    <th class="text-left px-4 py-3">Collected By</th>
                    <th class="text-right px-4 py-3">Amount</th>
                    <th class="text-left px-4 py-3">Notes</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse($payments as $payment)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $payment->paid_at->format('d M Y') }}</td>

                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ $payment->created_at->format('d M Y h:i A') }}
                        </td>

                        <td class="px-4 py-3 font-semibold">
                            PAY-{{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}
                        </td>

                        <td class="px-4 py-3">
                            @if($payment->invoice)
                                <a href="{{ route('tenant.invoices.show', [$tenant, $payment->invoice->id]) }}" class="font-semibold hover:underline">
                                    {{ $payment->invoice->invoice_number }}
                                </a>
                            @else
                                -
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ $payment->customer?->name ?? '-' }}</div>
                            <div class="text-xs text-slate-500">{{ $payment->customer?->phone ?? '-' }}</div>
                        </td>

                        <td class="px-4 py-3">
                            {{ ucwords(str_replace('_', ' ', $payment->method)) }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $payment->reference ?: '-' }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $payment->creator?->name ?? '-' }}
                        </td>

                        <td class="px-4 py-3 text-right font-semibold">
                            {{ $money($payment->amount) }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $payment->notes ?: '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-8 text-center text-slate-500">
                            No payment collections found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $payments->links() }}</div>
@endsection
