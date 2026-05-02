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
            <h2 class="text-2xl font-bold">Sales Ledger</h2>
            <p class="text-slate-500">Daily sales total from ledger entries.</p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('tenant.accounting.sales-ledger.download', array_merge($downloadQuery, ['tenant' => $tenant, 'format' => 'pdf'])) }}" target="_blank" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Download PDF
            </a>

            <a href="{{ route('tenant.accounting.sales-ledger.download', array_merge($downloadQuery, ['tenant' => $tenant, 'format' => 'csv'])) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
                Download CSV
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm mb-6">
        <div class="text-sm text-slate-500">Total Sales</div>
        <div class="text-3xl font-bold mt-2">{{ $money($totalSales) }}</div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-4">
        <form method="GET" action="{{ route('tenant.accounting.sales-ledger', $tenant) }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input type="date" name="from" value="{{ $from }}" class="rounded-lg border border-slate-300 px-3 py-2">
            <input type="date" name="to" value="{{ $to }}" class="rounded-lg border border-slate-300 px-3 py-2">
            <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Date</th>
                    <th class="text-right px-4 py-3">Sales</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($entries as $entry)
                    <tr>
                        <td class="px-4 py-3">{{ \Illuminate\Support\Carbon::parse($entry->entry_date)->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-right font-semibold">{{ $money($entry->total_sales) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-4 py-8 text-center text-slate-500">No sales found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $entries->links() }}</div>
@endsection
