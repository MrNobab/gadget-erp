@extends('layouts.tenant')

@section('content')
    <div class="mb-6">
        <h2 class="text-2xl font-bold">Dashboard</h2>
        <p class="text-slate-500">Welcome back, {{ $currentUser->name }}.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Shop</div>
            <div class="mt-2 text-xl font-bold">{{ $tenant->name }}</div>
            <div class="mt-1 text-xs text-slate-500">/{{ $tenant->slug }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Plan</div>
            <div class="mt-2 text-xl font-bold">{{ $tenant->license?->plan?->name ?? '-' }}</div>
            <div class="mt-1 text-xs text-slate-500">{{ ucfirst($tenant->license?->plan?->billing_cycle ?? '-') }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">License</div>
            <div class="mt-2 text-xl font-bold">{{ ucfirst($tenant->license?->status ?? 'missing') }}</div>
            <div class="mt-1 text-xs text-slate-500">
                Expires: {{ $tenant->license?->expires_at?->format('d M Y') ?? '-' }}
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Users</div>
            <div class="mt-2 text-xl font-bold">{{ $activeUserCount }} active</div>
            <div class="mt-1 text-xs text-slate-500">{{ $userCount }} total users</div>
        </div>
    </div>

    <div class="mt-8 bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
        <h3 class="text-lg font-bold mb-2">Next Modules</h3>
        <p class="text-slate-600 mb-4">
            Tenant login is now working. Next we will build tenant isolation and the product catalog.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-lg border border-slate-200 p-4 bg-slate-50">
                <div class="text-sm text-slate-500">Module 1</div>
                <div class="font-bold">Products</div>
            </div>

            <div class="rounded-lg border border-slate-200 p-4 bg-slate-50">
                <div class="text-sm text-slate-500">Module 2</div>
                <div class="font-bold">Inventory</div>
            </div>

            <div class="rounded-lg border border-slate-200 p-4 bg-slate-50">
                <div class="text-sm text-slate-500">Module 3</div>
                <div class="font-bold">Sales / POS</div>
            </div>
        </div>
    </div>
@endsection
