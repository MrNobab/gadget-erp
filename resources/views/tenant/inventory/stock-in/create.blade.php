@extends('layouts.tenant')

@section('content')
    <div class="mb-6">
        <h2 class="text-2xl font-bold">Stock In</h2>
        <p class="text-slate-500">Add purchased stock and update weighted average cost.</p>
    </div>

    <form method="POST" action="{{ route('tenant.stock-in.store', $tenant) }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 max-w-4xl space-y-5">
        @csrf

        <datalist id="productOptions">
            @foreach($products as $product)
                <option value="{{ $product->name }} — {{ $product->sku }}"></option>
            @endforeach
        </datalist>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700">Warehouse</label>
                <select name="warehouse_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">Select warehouse</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected(old('warehouse_id') == $warehouse->id)>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Search Product</label>
                <input type="text" id="productSearch" list="productOptions" placeholder="Type product name or SKU..." class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                <input type="hidden" name="product_id" id="productId" value="{{ old('product_id') }}">
                <p class="mt-1 text-xs text-slate-500">Select a product from the suggestions.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Quantity Purchased</label>
                <input type="number" min="1" name="quantity" value="{{ old('quantity', 1) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Unit Purchase Cost</label>
                <input type="number" step="0.01" min="0" name="unit_cost" value="{{ old('unit_cost', 0) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                <p class="mt-1 text-xs text-slate-500">This cost recalculates weighted average cost.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Supplier</label>
                <select name="supplier_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">No supplier selected</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">Add suppliers from Purchases > Suppliers.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Supplier Name (manual)</label>
                <input type="text" name="supplier_name" value="{{ old('supplier_name') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Reference / Invoice No</label>
                <input type="text" name="reference_no" value="{{ old('reference_no') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Purchase Date</label>
                <input type="date" name="purchased_at" value="{{ old('purchased_at', now()->format('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">Notes</label>
            <textarea name="notes" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('notes') }}</textarea>
        </div>

        <div class="rounded-lg bg-slate-50 border border-slate-200 p-4 text-sm text-slate-600">
            Formula:
            <strong>new average cost = (old stock value + new purchase value) / new total quantity</strong>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Save Stock In
            </button>

            <a href="{{ route('tenant.stock.index', $tenant) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
                Cancel
            </a>
        </div>
    </form>

    <script>
        const products = @json($products->map(fn ($product) => [
            'id' => $product->id,
            'label' => $product->name . ' — ' . $product->sku,
        ])->values());

        const productByLabel = {};
        const productLabelById = {};

        products.forEach(product => {
            productByLabel[product.label] = product.id;
            productLabelById[product.id] = product.label;
        });

        const productSearch = document.getElementById('productSearch');
        const productId = document.getElementById('productId');

        productSearch.addEventListener('input', function () {
            productId.value = productByLabel[this.value] || '';
        });

        if (productId.value && productLabelById[productId.value]) {
            productSearch.value = productLabelById[productId.value];
        }
    </script>
@endsection
