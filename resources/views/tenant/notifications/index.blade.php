@extends('layouts.tenant')

@section('content')
    <div class="mb-6">
        <h2 class="text-2xl font-bold">Notifications</h2>
        <p class="text-slate-500">Actionable work from transfers, stock, dues, purchases, and warranty claims.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <a href="{{ route('tenant.stock-transfers.warehouse-tasks', $tenant) }}" class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:border-slate-300">
            <div class="text-sm text-slate-500">Warehouse Tasks</div>
            <div class="text-3xl font-bold mt-2 text-amber-700">{{ $transferSummary['warehouse']['total'] ?? 0 }}</div>
        </a>

        <a href="{{ route('tenant.stock-transfers.shop-tasks', $tenant) }}" class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:border-slate-300">
            <div class="text-sm text-slate-500">Shop Tasks</div>
            <div class="text-3xl font-bold mt-2 text-blue-700">{{ $transferSummary['shop']['total'] ?? 0 }}</div>
        </a>

        <a href="{{ route('tenant.stock.index', $tenant) }}" class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:border-slate-300">
            <div class="text-sm text-slate-500">Low Stock</div>
            <div class="text-3xl font-bold mt-2 text-red-700">{{ $lowStocks->count() }}</div>
        </a>

        <a href="{{ route('tenant.after-sales.index', $tenant) }}" class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:border-slate-300">
            <div class="text-sm text-slate-500">Open Warranty</div>
            <div class="text-3xl font-bold mt-2 text-purple-700">{{ $warrantyClaims->count() }}</div>
        </a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200">
                <h3 class="font-bold">Low Stock</h3>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($lowStocks as $stock)
                    <div class="px-5 py-4 text-sm flex justify-between gap-4">
                        <span>
                            <span class="font-semibold">{{ $stock->product?->name }}</span>
                            <span class="block text-xs text-slate-500">{{ $stock->warehouse?->name }} / Threshold {{ $stock->product?->low_stock_threshold }}</span>
                        </span>
                        <strong class="text-red-700">{{ $stock->quantity }}</strong>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-slate-500">No low-stock alerts.</div>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200">
                <h3 class="font-bold">Customer Dues</h3>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($dueInvoices as $invoice)
                    <a href="{{ route('tenant.invoices.show', [$tenant, $invoice->id]) }}" class="block px-5 py-4 text-sm hover:bg-slate-50">
                        <div class="flex justify-between gap-4">
                            <span>
                                <span class="font-semibold">{{ $invoice->invoice_number }}</span>
                                <span class="block text-xs text-slate-500">{{ $invoice->customer?->name }} / {{ $invoice->invoice_date?->format('d M Y') }}</span>
                            </span>
                            <strong class="text-red-700">{{ nxpbd_money($invoice->due_amount, $tenant) }}</strong>
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-slate-500">No customer dues.</div>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200">
                <h3 class="font-bold">Supplier Dues</h3>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($supplierDues as $supplier)
                    <a href="{{ route('tenant.suppliers.index', $tenant) }}" class="block px-5 py-4 text-sm hover:bg-slate-50">
                        <div class="flex justify-between gap-4">
                            <span>
                                <span class="font-semibold">{{ $supplier->name }}</span>
                                <span class="block text-xs text-slate-500">{{ $supplier->phone ?: 'No phone' }}</span>
                            </span>
                            <strong class="text-red-700">{{ nxpbd_money($supplier->total_due, $tenant) }}</strong>
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-slate-500">No supplier dues.</div>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200">
                <h3 class="font-bold">Warranty Claims</h3>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($warrantyClaims as $claim)
                    <a href="{{ route('tenant.after-sales.index', $tenant) }}" class="block px-5 py-4 text-sm hover:bg-slate-50">
                        <div class="flex justify-between gap-4">
                            <span>
                                <span class="font-semibold">{{ $claim->customer?->name }}</span>
                                <span class="block text-xs text-slate-500">{{ $claim->product?->name ?? 'No product' }} / {{ $claim->opened_at?->format('d M Y') }}</span>
                            </span>
                            <span class="px-2 py-1 rounded-full bg-purple-50 text-purple-700 text-xs font-semibold">
                                {{ ucwords(str_replace('_', ' ', $claim->status)) }}
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-slate-500">No open warranty claims.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
