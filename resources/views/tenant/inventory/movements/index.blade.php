@extends('layouts.tenant')

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">Stock Movements</h2>
            <p class="text-slate-500">Audit trail of stock changes and average cost changes.</p>
        </div>

        <a href="{{ route('tenant.stock.index', $tenant) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
            Back to Stock
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-4">
        <form method="GET" action="{{ route('tenant.stock-movements.index', $tenant) }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search product or SKU..." class="rounded-lg border border-slate-300 px-3 py-2">

            <select name="type" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">All Types</option>
                <option value="in" @selected($type === 'in')>Stock In</option>
                <option value="out" @selected($type === 'out')>Stock Out</option>
                <option value="adjustment" @selected($type === 'adjustment')>Adjustment</option>
            </select>

            <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Filter
            </button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Date</th>
                    <th class="text-left px-4 py-3">Product</th>
                    <th class="text-left px-4 py-3">Warehouse</th>
                    <th class="text-left px-4 py-3">Type</th>
                    <th class="text-left px-4 py-3">Qty</th>
                    <th class="text-left px-4 py-3">Before</th>
                    <th class="text-left px-4 py-3">After</th>
                    <th class="text-left px-4 py-3">Avg Before</th>
                    <th class="text-left px-4 py-3">Avg After</th>
                    <th class="text-left px-4 py-3">Reason</th>
                    <th class="text-left px-4 py-3">User</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($movements as $movement)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $movement->created_at->format('d M Y h:i A') }}</td>
                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ $movement->product->name }}</div>
                            <div class="text-xs text-slate-500">{{ $movement->product->sku }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $movement->warehouse->name }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold
                                @if($movement->type === 'in') bg-green-50 text-green-700
                                @elseif($movement->type === 'out') bg-red-50 text-red-700
                                @else bg-yellow-50 text-yellow-700
                                @endif
                            ">
                                {{ ucfirst($movement->type) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-semibold">{{ $movement->quantity }}</td>
                        <td class="px-4 py-3">{{ $movement->before_qty }}</td>
                        <td class="px-4 py-3">{{ $movement->after_qty }}</td>
                        <td class="px-4 py-3">৳{{ number_format((float) $movement->before_average_cost, 2) }}</td>
                        <td class="px-4 py-3">৳{{ number_format((float) $movement->after_average_cost, 2) }}</td>
                        <td class="px-4 py-3">{{ $movement->reason ?: '-' }}</td>
                        <td class="px-4 py-3">{{ $movement->creator?->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-4 py-8 text-center text-slate-500">
                            No stock movements yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $movements->links() }}
    </div>
@endsection
