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
            <h2 class="text-2xl font-bold">Summary Report</h2>
            <p class="text-slate-500">Sales, collection, cost, expense, profit, and due movement.</p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('tenant.accounting.daily-summary.download', array_merge($downloadQuery, ['tenant' => $tenant, 'format' => 'pdf'])) }}" target="_blank" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Download PDF
            </a>

            <a href="{{ route('tenant.accounting.daily-summary.download', array_merge($downloadQuery, ['tenant' => $tenant, 'format' => 'csv'])) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
                Download CSV
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-6">
        <form method="GET" action="{{ route('tenant.accounting.daily-summary', $tenant) }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input type="date" name="from" value="{{ $from }}" class="rounded-lg border border-slate-300 px-3 py-2">
            <input type="date" name="to" value="{{ $to }}" class="rounded-lg border border-slate-300 px-3 py-2">
            <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">Load Summary</button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Total Sales</div>
            <div class="text-2xl font-bold mt-2">{{ $money($totalSales) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Cost of Goods</div>
            <div class="text-2xl font-bold mt-2">{{ $money($costOfGoods) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Gross Profit</div>
            <div class="text-2xl font-bold mt-2">{{ $money($grossProfit) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Due Increased</div>
            <div class="text-2xl font-bold mt-2">{{ $money($dueIncreased) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Due Collected</div>
            <div class="text-2xl font-bold mt-2">{{ $money($dueCollected) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Net Due Change</div>
            <div class="text-2xl font-bold mt-2">{{ $money($netDueChange) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Collections</div>
            <div class="text-2xl font-bold mt-2">{{ $money($collections) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Expenses</div>
            <div class="text-2xl font-bold mt-2">{{ $money($expenses) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Net Profit</div>
            <div class="text-2xl font-bold mt-2 {{ (float) $netProfit < 0 ? 'text-red-600' : 'text-green-700' }}">
                {{ $money($netProfit) }}
            </div>
        </div>
    </div>
@endsection
