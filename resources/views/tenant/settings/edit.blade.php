@extends('layouts.tenant')

@section('content')
    <div class="mb-6">
        <h2 class="text-2xl font-bold">Shop Settings</h2>
        <p class="text-slate-500">Manage invoice logo, address, tax, and currency settings.</p>
    </div>

    <form method="POST" action="{{ route('tenant.settings.update', $tenant) }}" enctype="multipart/form-data" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 max-w-4xl space-y-6">
        @csrf
        @method('PUT')

        <div>
            <h3 class="text-lg font-bold mb-3">Shop Information</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Shop Name</label>
                    <input type="text" name="store_name" value="{{ old('store_name', $tenant->name) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Shop Phone</label>
                    <input type="text" name="store_phone" value="{{ old('store_phone', $settings['store_phone'] ?? $tenant->owner_phone) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Shop Email</label>
                    <input type="email" name="store_email" value="{{ old('store_email', $settings['store_email'] ?? $tenant->owner_email) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Logo</label>
                    <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    <p class="mt-1 text-xs text-slate-500">Max 1MB. Use PNG, JPG, JPEG, or WebP.</p>
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-slate-700">Shop Address</label>
                <textarea name="store_address" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('store_address', $settings['store_address'] ?? '') }}</textarea>
            </div>

            @if(!empty($settings['logo_path']))
                <div class="mt-4">
                    <div class="text-sm font-medium text-slate-700 mb-2">Current Logo</div>
                    <img src="{{ asset($settings['logo_path']) }}" alt="Shop Logo" class="h-20 w-auto rounded-lg border border-slate-200 bg-white p-2">
                </div>
            @endif
        </div>

        <div>
            <h3 class="text-lg font-bold mb-3">Invoice & Tax</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Default Tax Percent</label>
                    <input type="number" step="0.01" min="0" max="100" name="tax_percent" value="{{ old('tax_percent', $settings['tax_percent'] ?? 0) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    <p class="mt-1 text-xs text-slate-500">This tax percent will auto-fill on POS invoices.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Invoice Prefix</label>
                    <input type="text" name="invoice_prefix" value="{{ old('invoice_prefix', $settings['invoice_prefix'] ?? 'INV') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    <p class="mt-1 text-xs text-slate-500">Example: INV, SALE, NX.</p>
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-slate-700">Invoice Footer</label>
                <textarea name="invoice_footer" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('invoice_footer', $settings['invoice_footer'] ?? 'Thank you for shopping with us.') }}</textarea>
            </div>
        </div>

        <div>
            <h3 class="text-lg font-bold mb-3">Currency</h3>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Currency Code</label>
                    <input type="text" name="currency_code" value="{{ old('currency_code', $settings['currency_code'] ?? 'BDT') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Currency Symbol</label>
                    <input type="text" name="currency_symbol" value="{{ old('currency_symbol', $settings['currency_symbol'] ?? '৳') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Symbol Placement</label>
                    <select name="currency_position" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="before" @selected(old('currency_position', $settings['currency_position'] ?? 'before') === 'before')>Before amount</option>
                        <option value="after" @selected(old('currency_position', $settings['currency_position'] ?? 'before') === 'after')>After amount</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Decimals</label>
                    <input type="number" min="0" max="4" name="currency_decimals" value="{{ old('currency_decimals', $settings['currency_decimals'] ?? 2) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
            </div>
        </div>

        <div class="rounded-lg bg-slate-50 border border-slate-200 p-4 text-sm text-slate-600">
            Example preview:
            <strong>{{ nxpbd_money(12500, $tenant) }}</strong>
            Current settings are used after saving.
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Save Settings
            </button>

            <a href="{{ route('tenant.dashboard', $tenant) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
                Cancel
            </a>
        </div>
    </form>
@endsection
