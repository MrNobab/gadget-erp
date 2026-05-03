@extends('layouts.tenant')

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">Returns & Warranty</h2>
            <p class="text-slate-500">Record returned items, restore stock, and track warranty claims.</p>
        </div>

        <a href="{{ route('tenant.invoices.index', $tenant) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
            Invoices
        </a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
        <form method="POST" action="{{ route('tenant.after-sales.returns.store', $tenant) }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
            @csrf

            <h3 class="font-bold text-lg">Record Sales Return</h3>

            <div>
                <label class="block text-sm font-medium text-slate-700">Sold Item</label>
                <select name="invoice_item_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">Select invoice item</option>
                    @foreach($invoiceItems as $item)
                        <option value="{{ $item->id }}" @selected(old('invoice_item_id') == $item->id)>
                            {{ $item->invoice?->invoice_number }} - {{ $item->product?->name }} - {{ $item->invoice?->customer?->name }} - Qty {{ $item->quantity }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Qty</label>
                    <input type="number" min="1" name="quantity" value="{{ old('quantity', 1) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Refund</label>
                    <input type="number" min="0" step="0.01" name="refund_amount" value="{{ old('refund_amount', 0) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Date</label>
                    <input type="date" name="returned_at" value="{{ old('returned_at', now()->format('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
            </div>

            <input type="text" name="reason" value="{{ old('reason') }}" placeholder="Reason" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            <textarea name="notes" rows="3" placeholder="Notes" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('notes') }}</textarea>

            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Save Return
            </button>
        </form>

        <form method="POST" action="{{ route('tenant.after-sales.warranty.store', $tenant) }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
            @csrf

            <h3 class="font-bold text-lg">Open Warranty Claim</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Customer</label>
                    <select name="customer_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">Select customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                                {{ $customer->name }} - {{ $customer->phone }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Claim Type</label>
                    <select name="claim_type" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="repair">Repair</option>
                        <option value="replacement">Replacement</option>
                        <option value="service_check">Service Check</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Related Sold Item</label>
                <select name="invoice_item_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">No invoice item selected</option>
                    @foreach($invoiceItems as $item)
                        <option value="{{ $item->id }}" @selected(old('invoice_item_id') == $item->id)>
                            {{ $item->invoice?->invoice_number }} - {{ $item->product?->name }} - {{ $item->invoice?->customer?->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Opened At</label>
                <input type="date" name="opened_at" value="{{ old('opened_at', now()->format('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>

            <textarea name="issue" rows="4" required placeholder="Customer issue / device condition" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('issue') }}</textarea>

            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Open Claim
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-x-auto">
            <div class="px-5 py-4 border-b border-slate-200">
                <h3 class="font-bold">Recent Returns</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">Date</th>
                        <th class="text-left px-4 py-3">Invoice</th>
                        <th class="text-left px-4 py-3">Product</th>
                        <th class="text-left px-4 py-3">Qty</th>
                        <th class="text-left px-4 py-3">Refund</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($returns as $return)
                        <tr>
                            <td class="px-4 py-3">{{ $return->returned_at?->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ $return->invoice?->invoice_number }}</td>
                            <td class="px-4 py-3">{{ $return->product?->name }}</td>
                            <td class="px-4 py-3">{{ $return->quantity }}</td>
                            <td class="px-4 py-3">{{ nxpbd_money($return->refund_amount, $tenant) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No returns yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-x-auto">
            <div class="px-5 py-4 border-b border-slate-200">
                <h3 class="font-bold">Warranty Claims</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">Opened</th>
                        <th class="text-left px-4 py-3">Customer</th>
                        <th class="text-left px-4 py-3">Product</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-left px-4 py-3">Update</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($warrantyClaims as $claim)
                        <tr>
                            <td class="px-4 py-3">{{ $claim->opened_at?->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ $claim->customer?->name }}</td>
                            <td class="px-4 py-3">{{ $claim->product?->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold">
                                    {{ ucwords(str_replace('_', ' ', $claim->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <details>
                                    <summary class="cursor-pointer font-semibold text-slate-900">Edit</summary>
                                    <form method="POST" action="{{ route('tenant.after-sales.warranty.update', [$tenant, $claim->id]) }}" class="mt-3 space-y-3 min-w-80">
                                        @csrf
                                        @method('PUT')
                                        <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                            @foreach(['open', 'in_progress', 'resolved', 'rejected'] as $status)
                                                <option value="{{ $status }}" @selected($claim->status === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                                            @endforeach
                                        </select>
                                        <input type="date" name="resolved_at" value="{{ $claim->resolved_at?->format('Y-m-d') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                        <textarea name="resolution" rows="2" placeholder="Resolution notes" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ $claim->resolution }}</textarea>
                                        <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold">Save</button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No warranty claims yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
