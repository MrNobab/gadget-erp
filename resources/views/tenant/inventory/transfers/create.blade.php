@extends('layouts.tenant')

@section('content')
    <div class="mb-6">
        <h2 class="text-2xl font-bold">Request Stock Transfer</h2>
        <p class="text-slate-500">Request stock from warehouse to shop, or from shop back to warehouse.</p>
    </div>

    <form method="POST" action="{{ route('tenant.stock-transfers.store', $tenant) }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700">From Location</label>
                <select name="source_warehouse_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">Select source</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected(old('source_warehouse_id') == $location->id)>
                            {{ $location->name }} — {{ ucwords($location->type) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">To Location</label>
                <select name="destination_warehouse_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">Select destination</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected(old('destination_warehouse_id') == $location->id)>
                            {{ $location->name }} — {{ ucwords($location->type) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between gap-4 mb-3">
                <h3 class="text-lg font-bold">Products</h3>

                <button type="button" id="addRowBtn" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                    Add Product
                </button>
            </div>

            <datalist id="productOptions">
                @foreach($products as $product)
                    <option value="{{ $product->name }} — {{ $product->sku }}"></option>
                @endforeach
            </datalist>

            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-4 py-3">Product</th>
                            <th class="text-left px-4 py-3">Quantity</th>
                            <th class="text-left px-4 py-3">Action</th>
                        </tr>
                    </thead>

                    <tbody id="itemsBody" class="divide-y divide-slate-100">
                        <tr id="emptyRow">
                            <td colspan="3" class="px-4 py-8 text-center text-slate-500">
                                No products added yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">Request Note</label>
            <textarea name="request_note" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('request_note') }}</textarea>
        </div>

        <div class="rounded-lg bg-yellow-50 border border-yellow-200 p-4 text-sm text-yellow-800">
            Stock will not be added to the destination until the transfer is received and confirmed.
        </div>

        <div class="flex gap-3">
            <button type="submit" class="px-5 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Submit Request
            </button>

            <a href="{{ route('tenant.stock-transfers.index', $tenant) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
                Cancel
            </a>
        </div>
    </form>

    <template id="itemTemplate">
        <tr data-row="item">
            <td class="px-4 py-3">
                <input type="text" list="productOptions" class="product-search w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Type product name or SKU...">
                <input type="hidden" class="product-id">
            </td>
            <td class="px-4 py-3">
                <input type="number" min="1" value="1" class="quantity w-32 rounded-lg border border-slate-300 px-3 py-2">
            </td>
            <td class="px-4 py-3">
                <button type="button" class="remove-row px-3 py-2 rounded-lg bg-red-50 text-red-700 text-xs font-semibold">
                    Remove
                </button>
            </td>
        </tr>
    </template>

    <script>
        const products = @json($productPayload);

        const byLabel = {};
        Object.values(products).forEach(product => {
            byLabel[product.label] = product.id;
        });

        let index = 0;

        const body = document.getElementById('itemsBody');
        const emptyRow = document.getElementById('emptyRow');
        const template = document.getElementById('itemTemplate');

        function updateEmpty() {
            emptyRow.classList.toggle('hidden', body.querySelectorAll('tr[data-row="item"]').length > 0);
        }

        function addRow() {
            const clone = template.content.cloneNode(true);
            const row = clone.querySelector('tr');
            const currentIndex = index++;

            const search = row.querySelector('.product-search');
            const idInput = row.querySelector('.product-id');
            const qty = row.querySelector('.quantity');

            idInput.name = `items[${currentIndex}][product_id]`;
            qty.name = `items[${currentIndex}][quantity]`;

            search.addEventListener('input', function () {
                idInput.value = byLabel[this.value] || '';
            });

            row.querySelector('.remove-row').addEventListener('click', function () {
                row.remove();
                updateEmpty();
            });

            body.appendChild(row);
            updateEmpty();
        }

        document.getElementById('addRowBtn').addEventListener('click', addRow);
    </script>
@endsection
