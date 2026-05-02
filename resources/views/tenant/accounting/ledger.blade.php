@extends('layouts.tenant')

@section('content')
    @php
        $symbol = $settings['currency_symbol'] ?? '৳';
        $position = $settings['currency_position'] ?? 'before';
        $money = fn ($amount) => $position === 'after'
            ? number_format((float) $amount, 2) . $symbol
            : $symbol . number_format((float) $amount, 2);

        $downloadQuery = request()->query();

        $actionLabel = function ($entry) {
            if ($entry->account_type === 'sales') return 'Sale Recorded';
            if ($entry->account_type === 'due' && (float) $entry->debit > 0) return 'Due Added';
            if ($entry->account_type === 'due' && (float) $entry->credit > 0) return 'Due Collected';
            if (in_array($entry->account_type, ['cash', 'bank', 'mobile_money', 'card']) && (float) $entry->debit > 0) return 'Money Received';
            if (in_array($entry->account_type, ['cash', 'bank', 'mobile_money', 'card']) && (float) $entry->credit > 0) return 'Money Paid Out';
            if ($entry->account_type === 'expense') return 'Expense';
            if ($entry->account_type === 'cost_of_goods') return 'Product Cost';
            return 'Entry';
        };

        $entryAmount = fn ($entry) => (float) $entry->debit > 0 ? $entry->debit : $entry->credit;
    @endphp

    @include('tenant.accounting.partials.nav')

    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">Ledger Report</h2>
            <p class="text-slate-500">Simplified business ledger for sales, due, collections, expenses, and product cost.</p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('tenant.accounting.ledger.download', array_merge($downloadQuery, ['tenant' => $tenant, 'format' => 'pdf'])) }}" target="_blank" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Download PDF
            </a>

            <a href="{{ route('tenant.accounting.ledger.download', array_merge($downloadQuery, ['tenant' => $tenant, 'format' => 'csv'])) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
                Download CSV
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Sales</div>
            <div class="text-2xl font-bold mt-2">{{ $money($totals['sales']) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Due Added</div>
            <div class="text-2xl font-bold mt-2">{{ $money($totals['dueAdded']) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Due Collected</div>
            <div class="text-2xl font-bold mt-2">{{ $money($totals['dueCollected']) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Expenses</div>
            <div class="text-2xl font-bold mt-2">{{ $money($totals['expenses']) }}</div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-4">
        <form method="GET" action="{{ route('tenant.accounting.ledger', $tenant) }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="date" name="from" value="{{ $from }}" class="rounded-lg border border-slate-300 px-3 py-2">
            <input type="date" name="to" value="{{ $to }}" class="rounded-lg border border-slate-300 px-3 py-2">

            <select name="account_type" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">Important Entries Only</option>
                @foreach($accountTypes as $key => $label)
                    <option value="{{ $key }}" @selected($accountType === $key)>{{ $label }}</option>
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
                    <th class="text-left px-4 py-3">Category</th>
                    <th class="text-left px-4 py-3">Action</th>
                    <th class="text-left px-4 py-3">Customer</th>
                    <th class="text-right px-4 py-3">Amount</th>
                    <th class="text-left px-4 py-3">Details</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($entries as $entry)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $entry->entry_date->format('d M Y') }}</td>
                        <td class="px-4 py-3 font-semibold">
                            {{ $entry->account_type === 'due' || $entry->account_type === 'receivable' ? 'Due' : ucwords(str_replace('_', ' ', $entry->account_type)) }}
                        </td>
                        <td class="px-4 py-3">{{ $actionLabel($entry) }}</td>
                        <td class="px-4 py-3">{{ $entry->customer?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-right font-semibold">{{ $money($entryAmount($entry)) }}</td>
                        <td class="px-4 py-3">{{ $entry->notes }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">No ledger entries found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $entries->links() }}</div>
@endsection
