@extends('layouts.tenant')

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">Purchase History</h2>
            <p class="text-slate-500">Supplier-linked stock-in records and purchase costs.</p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('tenant.stock-in.create', $tenant) }}" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Stock In
            </a>
            <a href="{{ route('tenant.suppliers.index', $tenant) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
                Suppliers
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Date</th>
                    <th class="text-left px-4 py-3">Product</th>
                    <th class="text-left px-4 py-3">Warehouse</th>
                    <th class="text-left px-4 py-3">Supplier</th>
                    <th class="text-left px-4 py-3">Qty</th>
                    <th class="text-left px-4 py-3">Unit Cost</th>
                    <th class="text-left px-4 py-3">Total</th>
                    <th class="text-left px-4 py-3">Reference</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse($purchases as $purchase)
                    <tr>
                        <td class="px-4 py-3">{{ $purchase->purchased_at?->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ $purchase->product?->name }}</div>
                            <div class="text-xs text-slate-500">{{ $purchase->product?->sku }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $purchase->warehouse?->name }}</td>
                        <td class="px-4 py-3">{{ $purchase->supplier?->name ?? $purchase->supplier_name ?? '-' }}</td>
                        <td class="px-4 py-3 font-semibold">{{ $purchase->quantity_purchased }}</td>
                        <td class="px-4 py-3">{{ nxpbd_money($purchase->unit_cost, $tenant) }}</td>
                        <td class="px-4 py-3 font-semibold">{{ nxpbd_money($purchase->total_cost, $tenant) }}</td>
                        <td class="px-4 py-3">{{ $purchase->reference_no ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-500">No purchase records yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $purchases->links() }}
    </div>
@endsection
