@extends('layouts.tenant')

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">Customers</h2>
            <p class="text-slate-500">Manage customer profiles, contact details, and due balances.</p>
        </div>

        <a href="{{ route('tenant.customers.create', $tenant) }}" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
            Add Customer
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-4">
        <form method="GET" action="{{ route('tenant.customers.index', $tenant) }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search name, phone, email..." class="rounded-lg border border-slate-300 px-3 py-2">

            <select name="status" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">All Status</option>
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
            </select>

            <select name="due_filter" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">All Due Status</option>
                <option value="has_due" @selected($dueFilter === 'has_due')>Has Due</option>
                <option value="no_due" @selected($dueFilter === 'no_due')>No Due</option>
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
                    <th class="text-left px-4 py-3">Customer</th>
                    <th class="text-left px-4 py-3">Contact</th>
                    <th class="text-left px-4 py-3">City</th>
                    <th class="text-left px-4 py-3">Purchases</th>
                    <th class="text-left px-4 py-3">Paid</th>
                    <th class="text-left px-4 py-3">Due</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($customers as $customer)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ $customer->name }}</div>
                            <div class="text-xs text-slate-500">ID: {{ $customer->id }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ $customer->phone ?: '-' }}</div>
                            <div class="text-xs text-slate-500">{{ $customer->email ?: '-' }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $customer->city ?: '-' }}</td>
                        <td class="px-4 py-3">৳{{ number_format((float) $customer->total_purchases, 2) }}</td>
                        <td class="px-4 py-3">৳{{ number_format((float) $customer->total_paid, 2) }}</td>
                        <td class="px-4 py-3">
                            @if((float) $customer->total_due > 0)
                                <span class="font-bold text-red-600">৳{{ number_format((float) $customer->total_due, 2) }}</span>
                            @else
                                <span class="text-slate-500">৳0.00</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($customer->is_active)
                                <span class="px-2 py-1 rounded-full bg-green-50 text-green-700 text-xs font-semibold">Active</span>
                            @else
                                <span class="px-2 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('tenant.customers.show', [$tenant, $customer->id]) }}" class="font-semibold text-slate-900 hover:underline">
                                    View
                                </a>

                                <a href="{{ route('tenant.customers.edit', [$tenant, $customer->id]) }}" class="font-semibold text-slate-900 hover:underline">
                                    Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-500">
                            No customers found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $customers->links() }}
    </div>
@endsection
