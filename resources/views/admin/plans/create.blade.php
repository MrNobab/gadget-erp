@extends('layouts.admin')

@section('content')
    <div class="mb-6">
        <h2 class="text-2xl font-bold">Create Plan</h2>
        <p class="text-slate-500">Create a license plan for tenants.</p>
    </div>

    <form method="POST" action="{{ route('admin.plans.store') }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 max-w-2xl space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-slate-700">Plan Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">Slug</label>
            <input type="text" name="slug" value="{{ old('slug') }}" placeholder="starter" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            <p class="mt-1 text-xs text-slate-500">Leave empty to auto-generate from name.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700">Price</label>
                <input type="number" step="0.01" min="0" name="price" value="{{ old('price', 1000) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Billing Cycle</label>
                <select name="billing_cycle" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="monthly" @selected(old('billing_cycle') === 'monthly')>Monthly</option>
                    <option value="yearly" @selected(old('billing_cycle') === 'yearly')>Yearly</option>
                    <option value="lifetime" @selected(old('billing_cycle') === 'lifetime')>Lifetime</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700">Max Users</label>
                <input type="number" min="1" name="max_users" value="{{ old('max_users', 3) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Max Products</label>
                <input type="number" min="1" name="max_products" value="{{ old('max_products', 500) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
        </div>

        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300">
            <span class="text-sm text-slate-700">Active</span>
        </label>

        <div class="flex items-center gap-3">
            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Save Plan
            </button>

            <a href="{{ route('admin.plans.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
                Cancel
            </a>
        </div>
    </form>
@endsection
