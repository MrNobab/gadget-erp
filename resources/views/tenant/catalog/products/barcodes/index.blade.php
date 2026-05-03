@extends('layouts.tenant')

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">Barcode Labels</h2>
            <p class="text-slate-500">Print product labels for scanner-ready selling and stock lookup.</p>
        </div>

        <a href="{{ route('tenant.products.index', $tenant) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
            Products
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-4">
        <form method="GET" action="{{ route('tenant.products.barcode-labels.index', $tenant) }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search name, SKU, or barcode..." class="md:col-span-3 rounded-lg border border-slate-300 px-3 py-2">

            <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Search
            </button>
        </form>
    </div>

    <form method="POST" action="{{ route('tenant.products.barcode-labels.print', $tenant) }}" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        @csrf

        <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="font-bold">Select Labels</h3>
                <p class="text-sm text-slate-500">Enter how many labels you want for each product, then print.</p>
            </div>

            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Print Selected
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">Product</th>
                        <th class="text-left px-4 py-3">Scan Code</th>
                        <th class="text-left px-4 py-3">Sale Price</th>
                        <th class="text-left px-4 py-3">Label Qty</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $product)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-semibold">{{ $product->name }}</div>
                                <div class="text-xs text-slate-500">SKU: {{ $product->sku }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-mono text-sm">{{ $product->barcodeValue() }}</div>
                                @if(! $product->barcode)
                                    <div class="text-xs text-amber-700">Using SKU as barcode</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ nxpbd_money($product->sale_price, $tenant) }}</td>
                            <td class="px-4 py-3">
                                <input type="hidden" name="products[{{ $product->id }}][product_id]" value="{{ $product->id }}">
                                <input type="number" min="0" max="200" name="products[{{ $product->id }}][quantity]" value="{{ old('products.' . $product->id . '.quantity', $search !== '' && $products->count() === 1 ? 1 : '') }}" placeholder="0" class="w-28 rounded-lg border border-slate-300 px-3 py-2">
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-500">No products found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <div class="mt-4">
        {{ $products->links() }}
    </div>
@endsection
