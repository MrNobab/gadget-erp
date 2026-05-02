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
            <h2 class="text-2xl font-bold">Cashbook</h2>
            <p class="text-slate-500">Cash, bank, mobile money, and card inflow/outflow.</p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('tenant.accounting.cashbook.download', array_merge($downloadQuery, ['tenant' => $tenant, 'format' => 'pdf'])) }}" target="_blank" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Download PDF
            </a>

            <a href="{{ route('tenant.accounting.cashbook.download', array_merge($downloadQuery, ['tenant' => $tenant, 'format' => 'csv'])) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
                Download CSV
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Total In</div>
            <div class="text-2xl font-bold mt-2">{{ $money($totalIn) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Total Out</div>
            <div class="text-2xl font-bold mt-2">{{ $money($totalOut) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Balance</div>
            <div class="text-2xl font-bold mt-2">{{ $money($balance) }}</div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-4">
        <form method="GET" action="{{ route('tenant.accounting.cashbook', $tenant) }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="date" name="from" value="{{ $from }}" class="rounded-lg border border-slate-300 px-3 py-2">
            <input type="date" name="to" value="{{ $to }}" class="rounded-lg border border-slate-300 px-3 py-2">

            <select name="account_type" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">All Payment Accounts</option>
                @foreach($paymentAccounts as $account)
                    <option value="{{ $account }}" @selected($accountType === $account)>{{ ucwords(str_replace('_', ' ', $account)) }}</option>
                @endforeach
            </select>

            <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Date</th>
                    <th class="text-left px-4 py-3">Account</th>
                    <th class="text-left px-4 py-3">Reference</th>
                    <th class="text-right px-4 py-3">In</th>
                    <th class="text-right px-4 py-3">Out</th>
                    <th class="text-right px-4 py-3">Balance</th>
                    <th class="text-left px-4 py-3">Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($entries as $entry)
                    <tr>
                        <td class="px-4 py-3">{{ $entry->entry_date->format('d M Y') }}</td>
                        <td class="px-4 py-3 font-semibold">{{ ucwords(str_replace('_', ' ', $entry->account_type)) }}</td>
                        <td class="px-4 py-3">{{ $entry->reference_type }} #{{ $entry->reference_id }}</td>
                        <td class="px-4 py-3 text-right">{{ $money($entry->debit) }}</td>
                        <td class="px-4 py-3 text-right">{{ $money($entry->credit) }}</td>
                        <td class="px-4 py-3 text-right font-semibold">{{ $money($entry->balance) }}</td>
                        <td class="px-4 py-3">{{ $entry->notes }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">No cashbook entries found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $entries->links() }}</div>
@endsection
