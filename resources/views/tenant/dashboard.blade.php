@extends('layouts.tenant')

@section('content')
    @php
        $stats = $dashboardStats ?? [];
        $warehouseTasks = $dashboardTransferSummary['warehouse'] ?? [];
        $shopTasks = $dashboardTransferSummary['shop'] ?? [];
    @endphp

    <div class="mb-6 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
        <div>
            <p class="text-sm font-semibold text-slate-500">Welcome back, {{ $currentUser->name }}</p>
            <h2 class="mt-1 text-3xl font-bold text-slate-950">Dashboard</h2>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Today</div>
            <div class="text-sm font-bold text-slate-900">{{ now()->format('d M Y') }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <a href="{{ route('tenant.pos.create', $tenant) }}" class="group rounded-lg bg-slate-950 p-5 text-white shadow-sm hover:bg-slate-800">
            <div class="text-sm font-semibold text-slate-300">Quick Action</div>
            <div class="mt-3 text-2xl font-bold">Create Invoice</div>
            <div class="mt-5 inline-flex rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-950 group-hover:bg-slate-100">
                New Sale
            </div>
        </a>

        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm font-semibold text-slate-500">Today's Sales</div>
            <div class="mt-3 text-2xl font-bold text-slate-950">{{ nxpbd_money($stats['today_sales'] ?? 0, $tenant) }}</div>
            <div class="mt-2 text-sm text-slate-500">{{ $stats['today_invoice_count'] ?? 0 }} invoices posted</div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm font-semibold text-slate-500">Outstanding Due</div>
            <div class="mt-3 text-2xl font-bold text-rose-700">{{ nxpbd_money($stats['outstanding_due'] ?? 0, $tenant) }}</div>
            <div class="mt-2 text-sm text-slate-500">{{ $stats['due_invoice_count'] ?? 0 }} invoices need collection</div>
        </div>

        <a href="{{ route('tenant.stock-transfers.warehouse-tasks', $tenant) }}" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm hover:border-slate-300">
            <div class="text-sm font-semibold text-slate-500">Transfer Tasks</div>
            <div class="mt-3 text-2xl font-bold text-amber-700">{{ $stats['transfer_task_count'] ?? 0 }}</div>
            <div class="mt-2 text-sm text-slate-500">
                Warehouse {{ $stats['warehouse_task_count'] ?? 0 }} / Shop {{ $stats['shop_task_count'] ?? 0 }}
            </div>
        </a>
    </div>

    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm font-semibold text-slate-500">Today's Collection</div>
            <div class="mt-3 text-xl font-bold text-emerald-700">{{ nxpbd_money($stats['today_collections'] ?? 0, $tenant) }}</div>
        </div>

        <a href="{{ route('tenant.stock.index', $tenant) }}" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm hover:border-slate-300">
            <div class="text-sm font-semibold text-slate-500">Stock Value</div>
            <div class="mt-3 text-xl font-bold text-slate-950">{{ nxpbd_money($stats['stock_value'] ?? 0, $tenant) }}</div>
            <div class="mt-2 text-sm text-slate-500">{{ $stats['low_stock_count'] ?? 0 }} low-stock items</div>
        </a>

        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-sm font-semibold text-slate-500">Active Records</div>
            <div class="mt-3 text-xl font-bold text-slate-950">{{ $stats['active_product_count'] ?? 0 }} products</div>
            <div class="mt-2 text-sm text-slate-500">{{ $stats['active_customer_count'] ?? 0 }} active customers</div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 xl:grid-cols-5 gap-4">
        <div class="xl:col-span-3 rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
                <div>
                    <h3 class="font-bold text-slate-950">Recent Invoices</h3>
                    <p class="text-sm text-slate-500">Latest posted sales activity.</p>
                </div>

                <a href="{{ route('tenant.invoices.index', $tenant) }}" class="text-sm font-semibold text-slate-900 hover:underline">View All</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-5 py-3">Invoice</th>
                            <th class="text-left px-5 py-3">Customer</th>
                            <th class="text-left px-5 py-3">Total</th>
                            <th class="text-left px-5 py-3">Due</th>
                            <th class="text-left px-5 py-3">Status</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentInvoices as $invoice)
                            <tr>
                                <td class="px-5 py-3 font-semibold">
                                    <a href="{{ route('tenant.invoices.show', [$tenant, $invoice->id]) }}" class="hover:underline">
                                        {{ $invoice->invoice_number }}
                                    </a>
                                    <div class="text-xs text-slate-500">{{ $invoice->invoice_date?->format('d M Y') }}</div>
                                </td>
                                <td class="px-5 py-3">{{ $invoice->customer?->name ?? '-' }}</td>
                                <td class="px-5 py-3 font-semibold">{{ nxpbd_money($invoice->total, $tenant) }}</td>
                                <td class="px-5 py-3">{{ nxpbd_money($invoice->due_amount, $tenant) }}</td>
                                <td class="px-5 py-3">
                                    <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $invoice->payment_status === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                        {{ ucwords($invoice->payment_status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-slate-500">No invoices posted yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="xl:col-span-2 rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="px-5 py-4 border-b border-slate-200">
                <h3 class="font-bold text-slate-950">Task Queue</h3>
                <p class="text-sm text-slate-500">Transfer work waiting for action.</p>
            </div>

            <div class="p-5 space-y-3">
                <a href="{{ route('tenant.stock-transfers.warehouse-tasks', $tenant) }}" class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 hover:border-slate-300">
                    <span>
                        <span class="block text-sm font-semibold text-slate-900">New Requests</span>
                        <span class="block text-xs text-slate-500">Warehouse approval needed</span>
                    </span>
                    <span class="rounded-full bg-yellow-100 px-2 py-1 text-xs font-bold text-yellow-800">{{ $warehouseTasks['requested'] ?? 0 }}</span>
                </a>

                <a href="{{ route('tenant.stock-transfers.warehouse-tasks', $tenant) }}" class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 hover:border-slate-300">
                    <span>
                        <span class="block text-sm font-semibold text-slate-900">Ready To Send</span>
                        <span class="block text-xs text-slate-500">Accepted transfers</span>
                    </span>
                    <span class="rounded-full bg-purple-100 px-2 py-1 text-xs font-bold text-purple-800">{{ $warehouseTasks['accepted'] ?? 0 }}</span>
                </a>

                <a href="{{ route('tenant.stock-transfers.shop-tasks', $tenant) }}" class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 hover:border-slate-300">
                    <span>
                        <span class="block text-sm font-semibold text-slate-900">Incoming To Receive</span>
                        <span class="block text-xs text-slate-500">Shop confirmation needed</span>
                    </span>
                    <span class="rounded-full bg-blue-100 px-2 py-1 text-xs font-bold text-blue-800">{{ $shopTasks['incoming'] ?? 0 }}</span>
                </a>

                <a href="{{ route('tenant.stock-transfers.warehouse-tasks', $tenant) }}" class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 hover:border-slate-300">
                    <span>
                        <span class="block text-sm font-semibold text-slate-900">Mark As Read</span>
                        <span class="block text-xs text-slate-500">Shop received, warehouse acknowledgement</span>
                    </span>
                    <span class="rounded-full bg-red-100 px-2 py-1 text-xs font-bold text-red-800">{{ $warehouseTasks['received_unacknowledged'] ?? 0 }}</span>
                </a>
            </div>
        </div>
    </div>
@endsection
