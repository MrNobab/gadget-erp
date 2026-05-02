@php
    $customer = $customer ?? null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700">Customer Name</label>
        <input type="text" name="name" value="{{ old('name', $customer?->name) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Phone</label>
        <input type="text" name="phone" value="{{ old('phone', $customer?->phone) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Email</label>
        <input type="email" name="email" value="{{ old('email', $customer?->email) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">City</label>
        <input type="text" name="city" value="{{ old('city', $customer?->city) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
    </div>
</div>

<div>
    <label class="block text-sm font-medium text-slate-700">Address</label>
    <textarea name="address" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('address', $customer?->address) }}</textarea>
</div>

<label class="flex items-center gap-2">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $customer?->is_active ?? true)) class="rounded border-slate-300">
    <span class="text-sm text-slate-700">Active</span>
</label>

<div class="flex items-center gap-3">
    <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
        {{ $submitText }}
    </button>

    <a href="{{ route('tenant.customers.index', $tenant) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
        Cancel
    </a>
</div>
