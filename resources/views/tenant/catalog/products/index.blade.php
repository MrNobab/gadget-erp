@extends('layouts.tenant')

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">Products</h2>
            <p class="text-slate-500">Manage product catalog without image uploads.</p>
        </div>

        <a href="{{ route('tenant.products.create', $tenant) }}" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
            Add Product
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-4">
        <form method="GET" action="{{ route('tenant.products.index', $tenant) }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search name or SKU..." class="rounded-lg border border-slate-300 px-3 py-2">

            <select name="category_id" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>

            <select name="brand_id" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">All Brands</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" @selected((string) $brandId === (string) $brand->id)>
                        {{ $brand->name }}
                    </option>
                @endforeach
            </select>

            <select name="status" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">All Status</option>
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
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
                    <th class="text-left px-4 py-3">Category</th>
                    <th class="text-left px-4 py-3">Brand</th>
                    <th class="text-left px-4 py-3">Cost</th>
                    <th class="text-left px-4 py-3">Sale</th>
                    <th class="text-left px-4 py-3">Warranty</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($products as $product)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ $product->name }}</div>
                            <div class="text-xs text-slate-500">SKU: {{ $product->sku }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $product->category?->name ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $product->brand?->name ?? '-' }}</td>
                        <td class="px-4 py-3">৳{{ number_format((float) $product->cost_price, 2) }}</td>
                        <td class="px-4 py-3">৳{{ number_format((float) $product->sale_price, 2) }}</td>
                        <td class="px-4 py-3">{{ $product->warranty_duration_months }} months</td>
                        <td class="px-4 py-3">
                            @if($product->is_active)
                                <span class="px-2 py-1 rounded-full bg-green-50 text-green-700 text-xs font-semibold">Active</span>
                            @else
                                <span class="px-2 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('tenant.products.edit', [$tenant, $product->id]) }}" class="font-semibold text-slate-900 hover:underline">
                                    Edit
                                </a>

                                <form method="POST" action="{{ route('tenant.products.destroy', [$tenant, $product->id]) }}" onsubmit="return confirm('Delete this product?')">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="font-semibold text-red-600 hover:underline">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-500">
                            No products found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $products->links() }}
    </div>
@endsection
