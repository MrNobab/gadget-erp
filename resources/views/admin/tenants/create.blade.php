@extends('layouts.admin')

@section('content')
    <div class="mb-6">
        <h2 class="text-2xl font-bold">Create Tenant</h2>
        <p class="text-slate-500">Create a new shop account with license and owner login.</p>
    </div>

    @if($plans->isEmpty())
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-xl p-4">
            You need to create at least one active plan before creating a tenant.
            <a href="{{ route('admin.plans.create') }}" class="font-semibold underline">Create plan</a>
        </div>
    @else
        <form method="POST" action="{{ route('admin.tenants.store') }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 max-w-3xl space-y-6">
            @csrf

            <div>
                <h3 class="text-lg font-bold mb-3">Shop Information</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Shop Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Shop Slug</label>
                        <input type="text" name="slug" value="{{ old('slug') }}" placeholder="demo-shop" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        <p class="mt-1 text-xs text-slate-500">Used later for tenant URL: /shop/demo-shop</p>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-bold mb-3">Owner Information</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Owner Name</label>
                        <input type="text" name="owner_name" value="{{ old('owner_name') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Owner Email</label>
                        <input type="email" name="owner_email" value="{{ old('owner_email') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Owner Phone</label>
                        <input type="text" name="owner_phone" value="{{ old('owner_phone') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-bold mb-3">Owner Login Password</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Password</label>
                        <input type="password" name="owner_password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Confirm Password</label>
                        <input type="password" name="owner_password_confirmation" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-bold mb-3">License</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Plan</label>
                        <select name="plan_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">Select plan</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" @selected(old('plan_id') == $plan->id)>
                                    {{ $plan->name }} — ৳{{ number_format((float) $plan->price, 2) }} / {{ $plan->billing_cycle }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">License Expires At</label>
                        <input type="date" name="expires_at" value="{{ old('expires_at', now()->addMonth()->format('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                    Create Tenant
                </button>

                <a href="{{ route('admin.tenants.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
                    Cancel
                </a>
            </div>
        </form>
    @endif
@endsection
