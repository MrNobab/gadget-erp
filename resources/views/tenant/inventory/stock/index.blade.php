@extends('layouts.tenant')

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">Stock Levels</h2>
            <p class="text-slate-500">Current stock by warehouse and shop. POS can only sell from shop locations.</p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('tenant.stock-in.create', $tenant) }}" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Stock In
            </a>

            <a href="{{ route('tenant.stock-transfers.index', $tenant) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
                Transfers
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-4">
        <form method="GET" action="{{ route('tenant.stock.index', $tenant) }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search product or SKU..." class="rounded-lg border border-slate-300 px-3 py-2">

            <select name="warehouse_id" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">All Locations</option>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected((string) $warehouseId === (string) $warehouse->id)>
                        {{ $warehouse->name }} — {{ ucwords($warehouse->type ?? 'warehouse') }}
                    </option>
                @endforeach
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
                    <th class="text-left px-4 py-3">Product</th>
                    <th class="text-left px-4 py-3">Location</th>
                    <th class="text-left px-4 py-3">Type</th>
                    <th class="text-left px-4 py-3">Qty</th>
                    <th class="text-left px-4 py-3">Avg Cost</th>
                    <th class="text-left px-4 py-3">Stock Value</th>
                    <th class="text-left px-4 py-3">Sale Price</th>
                    <th class="text-left px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($stocks as $stock)
                    @php
                        $isLow = $stock->quantity <= $stock->product->low_stock_threshold;
                    @endphp

                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ $stock->product->name }}</div>
                            <div class="text-xs text-slate-500">SKU: {{ $stock->product->sku }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $stock->warehouse->name }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ ($stock->warehouse->type ?? 'warehouse') === 'shop' ? 'bg-green-50 text-green-700' : 'bg-blue-50 text-blue-700' }}">
                                {{ ucwords($stock->warehouse->type ?? 'warehouse') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-semibold">{{ $stock->quantity }}</td>
                        <td class="px-4 py-3">৳{{ number_format((float) $stock->average_cost_price, 2) }}</td>
                        <td class="px-4 py-3">৳{{ number_format((float) $stock->stock_value, 2) }}</td>
                        <td class="px-4 py-3">৳{{ number_format((float) $stock->product->sale_price, 2) }}</td>
                        <td class="px-4 py-3">
                            @if($isLow)
                                <span class="px-2 py-1 rounded-full bg-red-50 text-red-700 text-xs font-semibold">Low Stock</span>
                            @else
                                <span class="px-2 py-1 rounded-full bg-green-50 text-green-700 text-xs font-semibold">OK</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-500">
                            No stock records yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $stocks->links() }}
    </div>

    <div class="mt-6 flex gap-4">
        <a href="{{ route('tenant.stock-transfers.index', $tenant) }}" class="text-sm font-semibold text-slate-900 hover:underline">
            View stock transfers
        </a>

        <a href="{{ route('tenant.stock-movements.index', $tenant) }}" class="text-sm font-semibold text-slate-900 hover:underline">
            View stock movement history
        </a>
    </div>
@endsection
