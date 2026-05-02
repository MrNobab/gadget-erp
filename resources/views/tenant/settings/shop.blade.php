@extends('layouts.tenant')

@section('content')
    @php
        $settings = $settings ?? [];
        $logoPath = $settings['logo_path'] ?? null;
    @endphp

    <div class="mb-6">
        <h2 class="text-2xl font-bold">Shop Settings</h2>
        <p class="text-slate-500">Manage invoice branding, tax, currency, and shop contact information.</p>
    </div>

    <form method="POST" action="{{ route('tenant.settings.shop.update', $tenant) }}" enctype="multipart/form-data" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 max-w-5xl space-y-8">
        @csrf
        @method('PUT')

        <div>
            <h3 class="text-lg font-bold mb-4">Basic Shop Information</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Shop Name</label>
                    <input type="text" name="shop_name" value="{{ old('shop_name', $tenant->name) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Shop Phone</label>
                    <input type="text" name="shop_phone" value="{{ old('shop_phone', $settings['shop_phone'] ?? $tenant->owner_phone) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Shop Email</label>
                    <input type="email" name="shop_email" value="{{ old('shop_email', $settings['shop_email'] ?? $tenant->owner_email) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Invoice Prefix</label>
                    <input type="text" name="invoice_prefix" value="{{ old('invoice_prefix', $settings['invoice_prefix'] ?? 'INV') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    <p class="mt-1 text-xs text-slate-500">Example: INV, NXP, SHOP. Used in invoice number.</p>
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-slate-700">Shop Address</label>
                <textarea name="shop_address" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('shop_address', $settings['shop_address'] ?? '') }}</textarea>
            </div>
        </div>

        <div>
            <h3 class="text-lg font-bold mb-4">Logo</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Upload Logo</label>
                    <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    <p class="mt-1 text-xs text-slate-500">PNG, JPG, or WEBP. Max 512KB. Keep it small for shared hosting.</p>

                    @if($logoPath)
                        <label class="mt-3 flex items-center gap-2">
                            <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300">
                            <span class="text-sm text-red-600">Remove current logo</span>
                        </label>
                    @endif
                </div>

                <div>
                    <div class="text-sm font-medium text-slate-700 mb-2">Current Logo</div>

                    @if($logoPath)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 inline-block">
                            <img src="{{ route('tenant.settings.shop.logo', $tenant) }}?v={{ $tenant->updated_at?->timestamp ?? time() }}" alt="Shop Logo" class="max-h-24 max-w-xs">
                        </div>
                    @else
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">
                            No logo uploaded yet.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-lg font-bold mb-4">Currency</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Currency Code</label>
                    <input type="text" name="currency_code" value="{{ old('currency_code', $settings['currency_code'] ?? 'BDT') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    <p class="mt-1 text-xs text-slate-500">Example: BDT, USD, INR.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Currency Symbol</label>
                    <input type="text" name="currency_symbol" value="{{ old('currency_symbol', $settings['currency_symbol'] ?? '৳') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    <p class="mt-1 text-xs text-slate-500">Example: ৳, $, ₹.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Currency Placement</label>
                    <select name="currency_position" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="before" @selected(old('currency_position', $settings['currency_position'] ?? 'before') === 'before')>Before amount — ৳1,000</option>
                        <option value="after" @selected(old('currency_position', $settings['currency_position'] ?? 'before') === 'after')>After amount — 1,000৳</option>
                    </select>
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-lg font-bold mb-4">Tax</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Default Tax Percent</label>
                    <input type="number" step="0.01" min="0" max="100" name="tax_percent" value="{{ old('tax_percent', $settings['tax_percent'] ?? 0) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    <p class="mt-1 text-xs text-slate-500">POS will use this tax percentage automatically.</p>
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-lg font-bold mb-4">Invoice Footer</h3>

            <textarea name="invoice_footer" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('invoice_footer', $settings['invoice_footer'] ?? 'Thank you for shopping with us.') }}</textarea>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="px-5 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Save Settings
            </button>

            <a href="{{ route('tenant.dashboard', $tenant) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
                Cancel
            </a>
        </div>
    </form>
@endsection
