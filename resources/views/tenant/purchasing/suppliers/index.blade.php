@extends('layouts.tenant')

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">Suppliers</h2>
            <p class="text-slate-500">Manage supplier contacts and purchase balances.</p>
        </div>

        <a href="{{ route('tenant.purchases.index', $tenant) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
            Purchase History
        </a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <form method="POST" action="{{ route('tenant.suppliers.store', $tenant) }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
            @csrf

            <h3 class="font-bold text-lg">Add Supplier</h3>

            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Supplier name" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            <input type="text" name="phone" value="{{ old('phone') }}" placeholder="Phone" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            <textarea name="address" rows="3" placeholder="Address" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('address') }}</textarea>

            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300">
                <span class="text-sm text-slate-700">Active supplier</span>
            </label>

            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Save Supplier
            </button>
        </form>

        <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">Supplier</th>
                        <th class="text-left px-4 py-3">Purchases</th>
                        <th class="text-left px-4 py-3">Due</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-left px-4 py-3">Update</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($suppliers as $supplier)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-semibold">{{ $supplier->name }}</div>
                                <div class="text-xs text-slate-500">{{ $supplier->phone ?: '-' }} {{ $supplier->email ? ' / ' . $supplier->email : '' }}</div>
                            </td>
                            <td class="px-4 py-3">{{ nxpbd_money($supplier->total_purchases, $tenant) }}</td>
                            <td class="px-4 py-3">{{ nxpbd_money($supplier->total_due, $tenant) }}</td>
                            <td class="px-4 py-3">
                                @if($supplier->is_active)
                                    <span class="px-2 py-1 rounded-full bg-green-50 text-green-700 text-xs font-semibold">Active</span>
                                @else
                                    <span class="px-2 py-1 rounded-full bg-red-50 text-red-700 text-xs font-semibold">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <details>
                                    <summary class="cursor-pointer font-semibold text-slate-900">Edit</summary>
                                    <form method="POST" action="{{ route('tenant.suppliers.update', [$tenant, $supplier->id]) }}" class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3 min-w-96">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="name" value="{{ $supplier->name }}" required class="rounded-lg border border-slate-300 px-3 py-2">
                                        <input type="text" name="phone" value="{{ $supplier->phone }}" class="rounded-lg border border-slate-300 px-3 py-2">
                                        <input type="email" name="email" value="{{ $supplier->email }}" class="rounded-lg border border-slate-300 px-3 py-2">
                                        <label class="flex items-center gap-2 text-sm text-slate-700">
                                            <input type="checkbox" name="is_active" value="1" @checked($supplier->is_active) class="rounded border-slate-300">
                                            Active
                                        </label>
                                        <textarea name="address" rows="2" class="md:col-span-2 rounded-lg border border-slate-300 px-3 py-2">{{ $supplier->address }}</textarea>
                                        <button type="submit" class="md:col-span-2 px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                                            Save Changes
                                        </button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No suppliers yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
