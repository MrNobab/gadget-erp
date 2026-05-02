@extends('layouts.tenant')

@section('content')
    @php
        $settings = $settings ?? ($tenant->settings ?? []);
        $currencySymbol = $settings['currency_symbol'] ?? '৳';
        $currencyPosition = $settings['currency_position'] ?? 'before';

        $money = function ($amount) use ($currencySymbol, $currencyPosition) {
            $formatted = number_format((float) $amount, 2);
            return $currencyPosition === 'after' ? $formatted . $currencySymbol : $currencySymbol . $formatted;
        };
    @endphp

    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">Invoices</h2>
            <p class="text-slate-500">Sales invoices, payment status, and dues.</p>
        </div>

        <a href="{{ route('tenant.pos.create', $tenant) }}" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
            New Invoice
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-4">
        <form method="GET" action="{{ route('tenant.invoices.index', $tenant) }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search invoice, customer, phone..." class="rounded-lg border border-slate-300 px-3 py-2">

            <select name="payment_status" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">All Payment Status</option>
                <option value="unpaid" @selected($paymentStatus === 'unpaid')>Unpaid</option>
                <option value="partial" @selected($paymentStatus === 'partial')>Partial</option>
                <option value="paid" @selected($paymentStatus === 'paid')>Paid</option>
            </select>

            <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Filter
            </button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Invoice</th>
                    <th class="text-left px-4 py-3">Customer</th>
                    <th class="text-left px-4 py-3">Total</th>
                    <th class="text-left px-4 py-3">Paid</th>
                    <th class="text-left px-4 py-3">Due</th>
                    <th class="text-left px-4 py-3">Payment</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($invoices as $invoice)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ $invoice->invoice_number }}</div>
                            <div class="text-xs text-slate-500">{{ $invoice->invoice_date->format('d M Y') }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ $invoice->customer->name }}</div>
                            <div class="text-xs text-slate-500">{{ $invoice->customer->phone }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $money($invoice->total) }}</td>
                        <td class="px-4 py-3">{{ $money($invoice->paid_amount) }}</td>
                        <td class="px-4 py-3">
                            <span class="{{ (float) $invoice->due_amount > 0 ? 'text-red-600 font-bold' : '' }}">
                                {{ $money($invoice->due_amount) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold
                                @if($invoice->payment_status === 'paid') bg-green-50 text-green-700
                                @elseif($invoice->payment_status === 'partial') bg-yellow-50 text-yellow-700
                                @else bg-red-50 text-red-700
                                @endif
                            ">
                                {{ ucfirst($invoice->payment_status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('tenant.invoices.show', [$tenant, $invoice->id]) }}" class="font-semibold text-slate-900 hover:underline">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                            No invoices found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $invoices->links() }}
    </div>
@endsection
