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
            <h2 class="text-2xl font-bold">Customer Due Ledger</h2>
            <p class="text-slate-500">Customer-wise total purchase, total paid, and total due.</p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('tenant.accounting.customer-ledger.download', array_merge($downloadQuery, ['tenant' => $tenant, 'format' => 'pdf'])) }}" target="_blank" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Download PDF
            </a>

            <a href="{{ route('tenant.accounting.customer-ledger.download', array_merge($downloadQuery, ['tenant' => $tenant, 'format' => 'csv'])) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
                Download CSV
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Total Purchase</div>
            <div class="text-2xl font-bold mt-2">{{ $money($totalPurchase) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Total Paid</div>
            <div class="text-2xl font-bold mt-2">{{ $money($totalPaid) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Total Due</div>
            <div class="text-2xl font-bold mt-2 {{ (float) $totalDue > 0 ? 'text-red-600' : 'text-green-700' }}">
                {{ $money($totalDue) }}
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-4">
        <form method="GET" action="{{ route('tenant.accounting.customer-ledger', $tenant) }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="customer_search" value="{{ $customerSearch }}" placeholder="Search customer name, phone, email..." class="rounded-lg border border-slate-300 px-3 py-2">

            <input type="date" name="from" value="{{ $from }}" class="rounded-lg border border-slate-300 px-3 py-2">
            <input type="date" name="to" value="{{ $to }}" class="rounded-lg border border-slate-300 px-3 py-2">

            <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Customer</th>
                    <th class="text-left px-4 py-3">Phone</th>
                    <th class="text-right px-4 py-3">Total Purchase</th>
                    <th class="text-right px-4 py-3">Total Paid</th>
                    <th class="text-right px-4 py-3">Total Due</th>
                    <th class="text-left px-4 py-3">Collect Due</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($customerRows as $row)
                    @php
                        $rowDue = (float) $row->total_due;
                    @endphp

                    <tr>
                        <td class="px-4 py-3 font-semibold">{{ $row->customer?->name ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $row->customer?->phone ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">{{ $money($row->total_purchase) }}</td>
                        <td class="px-4 py-3 text-right">{{ $money($row->total_paid) }}</td>
                        <td class="px-4 py-3 text-right font-bold {{ $rowDue > 0 ? 'text-red-600' : 'text-green-700' }}">
                            {{ $money($rowDue) }}
                        </td>
                        <td class="px-4 py-3">
                            @if($rowDue > 0 && $row->customer)
                                <form method="POST" action="{{ route('tenant.accounting.customer-dues.collect', [$tenant, $row->customer_id]) }}" class="flex flex-wrap gap-2 items-center">
                                    @csrf

                                    <input type="number" step="0.01" min="0.01" max="{{ $rowDue }}" name="amount" value="{{ number_format($rowDue, 2, '.', '') }}" class="w-28 rounded-lg border border-slate-300 px-2 py-1">

                                    <select name="method" class="w-28 rounded-lg border border-slate-300 px-2 py-1">
                                        <option value="cash">Cash</option>
                                        <option value="bank">Bank</option>
                                        <option value="mobile_money">Mobile</option>
                                        <option value="card">Card</option>
                                    </select>

                                    <input type="hidden" name="paid_at" value="{{ now()->format('Y-m-d') }}">
                                    <input type="text" name="reference" placeholder="Ref" class="w-24 rounded-lg border border-slate-300 px-2 py-1">

                                    <button type="submit" class="px-3 py-1 rounded-lg bg-slate-900 text-white text-xs font-semibold" onclick="return confirm('Collect this due payment?')">
                                        Collect
                                    </button>
                                </form>
                            @else
                                <span class="text-slate-400">No due</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">No customer due records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $customerRows->links() }}</div>
@endsection
