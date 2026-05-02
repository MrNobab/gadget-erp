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
            <h2 class="text-2xl font-bold">Invoice Dues</h2>
            <p class="text-slate-500">All posted invoices that still have unpaid due amount.</p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('tenant.accounting.due-collections.download', array_merge($downloadQuery, ['tenant' => $tenant, 'format' => 'pdf'])) }}" target="_blank" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Download PDF
            </a>

            <a href="{{ route('tenant.accounting.due-collections.download', array_merge($downloadQuery, ['tenant' => $tenant, 'format' => 'csv'])) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
                Download CSV
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Total Invoice Amount</div>
            <div class="text-3xl font-bold mt-2">{{ $money($totalInvoiceAmount) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Total Paid</div>
            <div class="text-3xl font-bold mt-2">{{ $money($totalPaidAmount) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Total Invoice Due</div>
            <div class="text-3xl font-bold mt-2 text-red-600">{{ $money($totalDueAmount) }}</div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-4">
        <form method="GET" action="{{ route('tenant.accounting.due-collections', $tenant) }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <input type="text" name="customer_search" value="{{ $customerSearch }}" placeholder="Search customer name, phone, email..." class="rounded-lg border border-slate-300 px-3 py-2">

            <select name="payment_status" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">All Due Status</option>
                <option value="unpaid" @selected($paymentStatus === 'unpaid')>Unpaid</option>
                <option value="partial" @selected($paymentStatus === 'partial')>Partial</option>
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
                    <th class="text-left px-4 py-3">Date</th>
                    <th class="text-left px-4 py-3">Invoice</th>
                    <th class="text-left px-4 py-3">Customer</th>
                    <th class="text-right px-4 py-3">Invoice Total</th>
                    <th class="text-right px-4 py-3">Paid</th>
                    <th class="text-right px-4 py-3">Invoice Due</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Collect Due</th>
                    <th class="text-left px-4 py-3">View</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse($invoices as $invoice)
                    @php
                        $invoiceDue = (float) $invoice->due_amount;
                    @endphp

                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $invoice->invoice_date->format('d M Y') }}</td>

                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ $invoice->invoice_number }}</div>
                        </td>

                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ $invoice->customer?->name ?? '-' }}</div>
                            <div class="text-xs text-slate-500">{{ $invoice->customer?->phone ?? '-' }}</div>
                        </td>

                        <td class="px-4 py-3 text-right">{{ $money($invoice->total) }}</td>
                        <td class="px-4 py-3 text-right">{{ $money($invoice->paid_amount) }}</td>

                        <td class="px-4 py-3 text-right font-bold text-red-600">
                            {{ $money($invoiceDue) }}
                        </td>

                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold
                                @if($invoice->payment_status === 'partial') bg-yellow-50 text-yellow-700
                                @else bg-red-50 text-red-700
                                @endif
                            ">
                                {{ ucfirst($invoice->payment_status) }}
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('tenant.accounting.invoice-dues.collect', [$tenant, $invoice->id]) }}" class="flex flex-wrap gap-2 items-center">
                                @csrf

                                <input type="number" step="0.01" min="0.01" max="{{ $invoiceDue }}" name="amount" value="{{ number_format($invoiceDue, 2, '.', '') }}" class="w-28 rounded-lg border border-slate-300 px-2 py-1">

                                <select name="method" class="w-28 rounded-lg border border-slate-300 px-2 py-1">
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank</option>
                                    <option value="mobile_money">Mobile</option>
                                    <option value="card">Card</option>
                                </select>

                                <input type="hidden" name="paid_at" value="{{ now()->format('Y-m-d') }}">
                                <input type="text" name="reference" placeholder="Ref" class="w-24 rounded-lg border border-slate-300 px-2 py-1">

                                <button type="submit" class="px-3 py-1 rounded-lg bg-slate-900 text-white text-xs font-semibold" onclick="return confirm('Collect this invoice due?')">
                                    Collect
                                </button>
                            </form>
                        </td>

                        <td class="px-4 py-3">
                            <a href="{{ route('tenant.invoices.show', [$tenant, $invoice->id]) }}" class="font-semibold text-slate-900 hover:underline">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-slate-500">
                            No due invoices found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $invoices->links() }}</div>
@endsection
