@php
    $product = $product ?? null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700">Product Name</label>
        <input type="text" name="name" value="{{ old('name', $product?->name) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">SKU</label>
        <input type="text" name="sku" value="{{ old('sku', $product?->sku) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Barcode / Scan Code</label>
        <input type="text" name="barcode" value="{{ old('barcode', $product?->barcode) }}" placeholder="Optional - leave blank to use SKU" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
        <p class="mt-1 text-xs text-slate-500">Use the printed product barcode or your own internal barcode. POS will also scan SKU.</p>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Category</label>
        <select name="category_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="">No category</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $product?->category_id) === (string) $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Brand</label>
        <select name="brand_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            <option value="">No brand</option>
            @foreach($brands as $brand)
                <option value="{{ $brand->id }}" @selected((string) old('brand_id', $product?->brand_id) === (string) $brand->id)>
                    {{ $brand->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Opening / Default Cost Price</label>
        <input type="number" step="0.01" min="0" name="cost_price" value="{{ old('cost_price', $product?->cost_price ?? 0) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Sale Price</label>
        <input type="number" step="0.01" min="0" name="sale_price" value="{{ old('sale_price', $product?->sale_price ?? 0) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Low Stock Threshold</label>
        <input type="number" min="0" name="low_stock_threshold" value="{{ old('low_stock_threshold', $product?->low_stock_threshold ?? 5) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Warranty Duration Months</label>
        <input type="number" min="0" max="120" name="warranty_duration_months" value="{{ old('warranty_duration_months', $product?->warranty_duration_months ?? 0) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
    </div>
</div>

<div>
    <label class="block text-sm font-medium text-slate-700">Description</label>
    <textarea name="description" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('description', $product?->description) }}</textarea>
</div>

<label class="flex items-center gap-2">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product?->is_active ?? true)) class="rounded border-slate-300">
    <span class="text-sm text-slate-700">Active</span>
</label>

<div class="flex items-center gap-3">
    <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
        {{ $submitText }}
    </button>

    <a href="{{ route('tenant.products.index', $tenant) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
        Cancel
    </a>
</div>
