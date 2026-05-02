@extends('layouts.tenant')

@section('content')
    @php
        $currencySymbol = $settings['currency_symbol'] ?? '৳';
        $currencyPosition = $settings['currency_position'] ?? 'before';
        $moneyPreview = $currencyPosition === 'after' ? '1,000' . $currencySymbol : $currencySymbol . '1,000';

        $customerPayloadForJs = $customerPayload ?? $customers->mapWithKeys(function ($customer): array {
            return [
                $customer->id => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'label' => $customer->name . ' — ' . $customer->phone,
                ],
            ];
        })->toArray();
    @endphp

    <div class="mb-6">
        <h2 class="text-2xl font-bold">POS / New Invoice</h2>
        <p class="text-slate-500">Search customers and products by typing name, phone, or SKU.</p>
    </div>

    <form method="POST" action="{{ route('tenant.pos.store', $tenant) }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6" id="posForm">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700">Warehouse</label>
                <select name="warehouse_id" id="warehouseId" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">Select warehouse</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected(old('warehouse_id') == $warehouse->id || (!old('warehouse_id') && $warehouse->is_default))>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Invoice Date</label>
                <input type="date" name="invoice_date" value="{{ old('invoice_date', now()->format('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Default Tax</label>
                <input type="number" value="{{ $settings['tax_percent'] ?? 0 }}" readonly class="mt-1 w-full rounded-lg border border-slate-300 bg-slate-100 px-3 py-2">
                <p class="mt-1 text-xs text-slate-500">Change this from Shop Settings.</p>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-lg font-bold">Customer</h3>
                    <p class="text-sm text-slate-500">Type customer name or phone. Or create a customer instantly.</p>
                </div>

                <div class="flex rounded-lg border border-slate-300 bg-white overflow-hidden">
                    <label class="px-3 py-2 text-sm cursor-pointer">
                        <input type="radio" name="customer_mode" value="existing" class="mr-1" @checked(old('customer_mode', 'existing') === 'existing')>
                        Existing
                    </label>
                    <label class="px-3 py-2 text-sm cursor-pointer border-l border-slate-300">
                        <input type="radio" name="customer_mode" value="new" class="mr-1" @checked(old('customer_mode') === 'new')>
                        New Customer
                    </label>
                </div>
            </div>

            <div id="existingCustomerBox">
                <label class="block text-sm font-medium text-slate-700">Search Existing Customer</label>
                <input type="text" id="customerSearch" list="customerOptions" placeholder="Type customer name or phone..." class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                <input type="hidden" name="customer_id" id="customerId" value="{{ old('customer_id') }}">
                <p class="mt-1 text-xs text-slate-500">Select a customer from the suggestions.</p>

                <datalist id="customerOptions">
                    @foreach($customers as $customer)
                        <option value="{{ $customer->name }} — {{ $customer->phone }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div id="newCustomerBox" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Customer Name</label>
                    <input type="text" name="quick_customer_name" value="{{ old('quick_customer_name') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Customer Phone</label>
                    <input type="text" name="quick_customer_phone" value="{{ old('quick_customer_phone') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Customer Email</label>
                    <input type="email" name="quick_customer_email" value="{{ old('quick_customer_email') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Customer Address</label>
                    <input type="text" name="quick_customer_address" value="{{ old('quick_customer_address') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between gap-4 mb-3">
                <div>
                    <h3 class="text-lg font-bold">Invoice Items</h3>
                    <p class="text-sm text-slate-500">Search product by name or SKU. Selling price is editable and saved with invoice.</p>
                </div>

                <button type="button" id="addItemBtn" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
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
                            <th class="text-left px-4 py-3 min-w-96">Product Search</th>
                            <th class="text-left px-4 py-3">SKU</th>
                            <th class="text-left px-4 py-3">Available</th>
                            <th class="text-left px-4 py-3">Qty</th>
                            <th class="text-left px-4 py-3">Selling Price</th>
                            <th class="text-left px-4 py-3">Line Total</th>
                            <th class="text-left px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody" class="divide-y divide-slate-100">
                        <tr id="noItemsRow">
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                                No products added yet. Click Add Product.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Discount Amount</label>
                        <input type="number" step="0.01" min="0" name="discount_amount" id="discountAmount" value="{{ old('discount_amount', 0) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Paid Amount</label>
                        <input type="number" step="0.01" min="0" name="paid_amount" id="paidAmount" value="{{ old('paid_amount', 0) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Payment Method</label>
                        <select name="payment_method" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="cash" @selected(old('payment_method') === 'cash')>Cash</option>
                            <option value="bank" @selected(old('payment_method') === 'bank')>Bank</option>
                            <option value="mobile_money" @selected(old('payment_method') === 'mobile_money')>Mobile Money</option>
                            <option value="card" @selected(old('payment_method') === 'card')>Card</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Payment Reference</label>
                    <input type="text" name="payment_reference" value="{{ old('payment_reference') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Notes</label>
                    <textarea name="notes" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-5 space-y-3">
                <h3 class="font-bold text-lg">Invoice Summary</h3>

                <div class="flex justify-between text-sm">
                    <span>Subtotal</span>
                    <strong id="subtotalPreview">{{ $moneyPreview }}</strong>
                </div>

                <div class="flex justify-between text-sm">
                    <span>Discount</span>
                    <strong id="discountPreview">{{ $moneyPreview }}</strong>
                </div>

                <div class="flex justify-between text-sm">
                    <span>Tax {{ number_format((float) ($settings['tax_percent'] ?? 0), 2) }}%</span>
                    <strong id="taxPreview">{{ $moneyPreview }}</strong>
                </div>

                <div class="flex justify-between border-t border-slate-200 pt-3 text-lg">
                    <span>Total</span>
                    <strong id="totalPreview">{{ $moneyPreview }}</strong>
                </div>

                <div class="flex justify-between text-sm">
                    <span>Paid</span>
                    <strong id="paidPreview">{{ $moneyPreview }}</strong>
                </div>

                <div class="flex justify-between text-sm">
                    <span>Due</span>
                    <strong id="duePreview" class="text-red-600">{{ $moneyPreview }}</strong>
                </div>
            </div>
        </div>

        <div class="rounded-lg bg-yellow-50 border border-yellow-200 p-4 text-sm text-yellow-800">
            Posting this invoice will immediately deduct stock and save the selling price you entered with the invoice.
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="px-5 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Post Invoice
            </button>

            <a href="{{ route('tenant.invoices.index', $tenant) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
                Invoice List
            </a>
        </div>
    </form>

    <template id="itemRowTemplate">
        <tr>
            <td class="px-4 py-3">
                <input type="text" list="productOptions" class="product-search-input w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Type product name or SKU...">
                <input type="hidden" class="product-id-input">
            </td>

            <td class="px-4 py-3">
                <input type="text" class="sku-input w-36 rounded-lg border border-slate-300 bg-slate-100 px-3 py-2" readonly>
            </td>

            <td class="px-4 py-3">
                <input type="text" class="available-input w-28 rounded-lg border border-slate-300 bg-slate-100 px-3 py-2" readonly>
            </td>

            <td class="px-4 py-3">
                <input type="number" min="1" class="qty-input w-24 rounded-lg border border-slate-300 px-3 py-2">
            </td>

            <td class="px-4 py-3">
                <input type="number" step="0.01" min="0" class="price-input w-32 rounded-lg border border-slate-300 px-3 py-2">
            </td>

            <td class="px-4 py-3">
                <input type="text" class="line-total-input w-36 rounded-lg border border-slate-300 bg-slate-100 px-3 py-2" readonly>
            </td>

            <td class="px-4 py-3">
                <button type="button" class="remove-row-btn px-3 py-2 rounded-lg bg-red-50 text-red-700 text-sm font-semibold">
                    Remove
                </button>
            </td>
        </tr>
    </template>

    <script>
        const products = @json($productPayload);
        const customers = @json($customerPayloadForJs);
        const stockMatrix = @json($stockMatrix);
        const oldItems = @json(old('items', []));
        const oldCustomerId = @json(old('customer_id'));
        const currencySymbol = @json($currencySymbol);
        const currencyPosition = @json($currencyPosition);
        const taxPercent = Number(@json((float) ($settings['tax_percent'] ?? 0)));

        const productByLabel = {};
        const productLabelById = {};
        Object.values(products).forEach(product => {
            const label = `${product.name} — ${product.sku}`;
            productByLabel[label] = product;
            productLabelById[product.id] = label;
        });

        const customerByLabel = {};
        const customerLabelById = {};
        Object.values(customers).forEach(customer => {
            customerByLabel[customer.label] = customer;
            customerLabelById[customer.id] = customer.label;
        });

        let rowIndex = 0;

        const itemsBody = document.getElementById('itemsBody');
        const noItemsRow = document.getElementById('noItemsRow');
        const template = document.getElementById('itemRowTemplate');

        const warehouseSelect = document.getElementById('warehouseId');
        const addItemBtn = document.getElementById('addItemBtn');
        const discountInput = document.getElementById('discountAmount');
        const paidInput = document.getElementById('paidAmount');

        const customerSearch = document.getElementById('customerSearch');
        const customerId = document.getElementById('customerId');

        function money(amount) {
            const formatted = Number(amount || 0).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            return currencyPosition === 'after'
                ? formatted + currencySymbol
                : currencySymbol + formatted;
        }

        function selectedWarehouseId() {
            return warehouseSelect.value || '';
        }

        function availableStock(productId) {
            const warehouseId = selectedWarehouseId();

            if (!warehouseId || !productId) {
                return 0;
            }

            if (!stockMatrix[warehouseId] || !stockMatrix[warehouseId][productId]) {
                return 0;
            }

            return Number(stockMatrix[warehouseId][productId].quantity || 0);
        }

        function updateNoItemsRow() {
            const realRows = itemsBody.querySelectorAll('tr[data-row="item"]').length;
            noItemsRow.classList.toggle('hidden', realRows > 0);
        }

        function addItemRow(item = {}) {
            const clone = template.content.cloneNode(true);
            const row = clone.querySelector('tr');

            row.dataset.row = 'item';

            const currentIndex = rowIndex++;

            const productSearchInput = row.querySelector('.product-search-input');
            const productIdInput = row.querySelector('.product-id-input');
            const skuInput = row.querySelector('.sku-input');
            const availableInput = row.querySelector('.available-input');
            const qtyInput = row.querySelector('.qty-input');
            const priceInput = row.querySelector('.price-input');
            const lineTotalInput = row.querySelector('.line-total-input');
            const removeBtn = row.querySelector('.remove-row-btn');

            productIdInput.name = `items[${currentIndex}][product_id]`;
            qtyInput.name = `items[${currentIndex}][quantity]`;
            priceInput.name = `items[${currentIndex}][unit_price]`;

            function fillProductDetails(forcePrice = true) {
                const product = productByLabel[productSearchInput.value];

                if (!product) {
                    productIdInput.value = '';
                    skuInput.value = '';
                    availableInput.value = '';
                    calculateRow();
                    return;
                }

                productIdInput.value = product.id;
                skuInput.value = product.sku || '';
                availableInput.value = availableStock(product.id);

                if (!qtyInput.value || Number(qtyInput.value) <= 0) {
                    qtyInput.value = 1;
                }

                if (forcePrice || !priceInput.value) {
                    priceInput.value = Number(product.sale_price || 0).toFixed(2);
                }

                calculateRow();
            }

            function calculateRow() {
                const qty = Number(qtyInput.value || 0);
                const price = Number(priceInput.value || 0);
                lineTotalInput.value = money(qty * price);
                calculateSummary();
            }

            productSearchInput.addEventListener('input', () => fillProductDetails(true));
            qtyInput.addEventListener('input', calculateRow);
            priceInput.addEventListener('input', calculateRow);

            removeBtn.addEventListener('click', function () {
                row.remove();
                updateNoItemsRow();
                calculateSummary();
            });

            if (item.product_id && productLabelById[item.product_id]) {
                productSearchInput.value = productLabelById[item.product_id];
            }

            if (item.quantity) {
                qtyInput.value = item.quantity;
            }

            if (item.unit_price) {
                priceInput.value = item.unit_price;
            }

            itemsBody.appendChild(row);

            if (item.product_id) {
                fillProductDetails(false);
            }

            updateNoItemsRow();
            calculateSummary();
        }

        function calculateSummary() {
            let subtotal = 0;

            itemsBody.querySelectorAll('tr[data-row="item"]').forEach(row => {
                const qty = Number(row.querySelector('.qty-input').value || 0);
                const price = Number(row.querySelector('.price-input').value || 0);
                subtotal += qty * price;
            });

            const discount = Math.max(0, Number(discountInput.value || 0));
            const taxable = Math.max(0, subtotal - discount);
            const tax = taxable * taxPercent / 100;
            const total = taxable + tax;
            const paid = Math.max(0, Number(paidInput.value || 0));
            const due = Math.max(0, total - paid);

            document.getElementById('subtotalPreview').textContent = money(subtotal);
            document.getElementById('discountPreview').textContent = money(discount);
            document.getElementById('taxPreview').textContent = money(tax);
            document.getElementById('totalPreview').textContent = money(total);
            document.getElementById('paidPreview').textContent = money(paid);
            document.getElementById('duePreview').textContent = money(due);
        }

        addItemBtn.addEventListener('click', () => addItemRow());

        warehouseSelect.addEventListener('change', function () {
            itemsBody.querySelectorAll('tr[data-row="item"]').forEach(row => {
                const productId = row.querySelector('.product-id-input').value;
                row.querySelector('.available-input').value = availableStock(productId);
            });
        });

        discountInput.addEventListener('input', calculateSummary);
        paidInput.addEventListener('input', calculateSummary);

        customerSearch.addEventListener('input', function () {
            const customer = customerByLabel[this.value];
            customerId.value = customer ? customer.id : '';
        });

        if (oldCustomerId && customerLabelById[oldCustomerId]) {
            customerSearch.value = customerLabelById[oldCustomerId];
            customerId.value = oldCustomerId;
        }

        document.querySelectorAll('input[name="customer_mode"]').forEach(radio => {
            radio.addEventListener('change', function () {
                const isNew = this.value === 'new' && this.checked;
                document.getElementById('existingCustomerBox').classList.toggle('hidden', isNew);
                document.getElementById('newCustomerBox').classList.toggle('hidden', !isNew);
            });
        });

        const currentCustomerMode = document.querySelector('input[name="customer_mode"]:checked')?.value || 'existing';
        document.getElementById('existingCustomerBox').classList.toggle('hidden', currentCustomerMode === 'new');
        document.getElementById('newCustomerBox').classList.toggle('hidden', currentCustomerMode !== 'new');

        if (Array.isArray(oldItems) && oldItems.length > 0) {
            oldItems.forEach(item => {
                if (item.product_id) {
                    addItemRow(item);
                }
            });
        }

        calculateSummary();
        updateNoItemsRow();
    </script>
@endsection
