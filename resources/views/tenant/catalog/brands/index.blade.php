@extends('layouts.tenant')

@section('content')
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">Brands</h2>
            <p class="text-slate-500">Manage product brands.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <h3 class="text-lg font-bold mb-4">Add Brand</h3>

            <form method="POST" action="{{ route('tenant.brands.store', $tenant) }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700">Name</label>
                    <input type="text" name="name" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300">
                    <span class="text-sm text-slate-700">Active</span>
                </label>

                <button type="submit" class="w-full px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                    Save Brand
                </button>
            </form>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-4">
                <form method="GET" action="{{ route('tenant.brands.index', $tenant) }}" class="flex gap-3">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search brand..." class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">Search</button>
                </form>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-4 py-3">Name</th>
                            <th class="text-left px-4 py-3">Products</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-left px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($brands as $brand)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold">{{ $brand->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $brand->slug }}</div>
                                </td>
                                <td class="px-4 py-3">{{ $brand->products_count }}</td>
                                <td class="px-4 py-3">
                                    @if($brand->is_active)
                                        <span class="px-2 py-1 rounded-full bg-green-50 text-green-700 text-xs font-semibold">Active</span>
                                    @else
                                        <span class="px-2 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <details class="relative">
                                        <summary class="cursor-pointer font-semibold text-slate-900">Edit</summary>

                                        <div class="absolute right-0 z-10 mt-2 w-80 bg-white border border-slate-200 rounded-xl shadow-lg p-4">
                                            <form method="POST" action="{{ route('tenant.brands.update', [$tenant, $brand->id]) }}" class="space-y-3">
                                                @csrf
                                                @method('PUT')

                                                <input type="text" name="name" value="{{ $brand->name }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2">

                                                <label class="flex items-center gap-2">
                                                    <input type="checkbox" name="is_active" value="1" @checked($brand->is_active) class="rounded border-slate-300">
                                                    <span class="text-sm text-slate-700">Active</span>
                                                </label>

                                                <button type="submit" class="w-full px-3 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                                                    Update
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('tenant.brands.destroy', [$tenant, $brand->id]) }}" class="mt-3" onsubmit="return confirm('Delete this brand?')">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="w-full px-3 py-2 rounded-lg bg-red-50 text-red-700 text-sm font-semibold">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-slate-500">No brands found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $brands->links() }}
            </div>
        </div>
    </div>
@endsection
