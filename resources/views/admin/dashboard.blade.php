@extends('layouts.admin')

@section('content')
    <div class="mb-6">
        <h2 class="text-2xl font-bold">Super Admin Dashboard</h2>
        <p class="text-slate-500">Manage the Gadget ERP SaaS platform.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Tenants</div>
            <div class="mt-2 text-3xl font-bold">{{ $tenantCount }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Plans</div>
            <div class="mt-2 text-3xl font-bold">{{ $planCount }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Active Licenses</div>
            <div class="mt-2 text-3xl font-bold">{{ $activeLicenseCount }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Frozen Licenses</div>
            <div class="mt-2 text-3xl font-bold">{{ $frozenLicenseCount }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Suspended Licenses</div>
            <div class="mt-2 text-3xl font-bold">{{ $suspendedLicenseCount }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Expired Licenses</div>
            <div class="mt-2 text-3xl font-bold">{{ $expiredLicenseCount }}</div>
        </div>
    </div>

    <div class="mt-8 bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <h3 class="text-lg font-bold mb-4">ERP Owner Branding</h3>

        <form method="POST" action="{{ route('admin.branding.update') }}" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-slate-700">Brand Name</label>
                <input type="text" name="brand_name" value="{{ old('brand_name', $superAdmin?->brand_name ?? 'Gadget ERP') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Brand Tagline</label>
                <input type="text" name="brand_tagline" value="{{ old('brand_tagline', $superAdmin?->brand_tagline ?? 'NexproBD Retail SaaS') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Owner Logo</label>
                <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">

                @if($superAdmin?->logo_path)
                    <div class="mt-3 flex items-center gap-3">
                        <img src="{{ route('admin.branding.logo') }}?v={{ $superAdmin->updated_at?->timestamp ?? time() }}" alt="ERP Owner Logo" class="h-10 w-10 rounded-lg object-contain border border-slate-200 bg-slate-50">
                        <label class="flex items-center gap-2 text-sm text-red-600">
                            <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300">
                            Remove logo
                        </label>
                    </div>
                @endif
            </div>

            <div class="lg:col-span-3">
                <button type="submit" class="px-5 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                    Save Branding
                </button>
            </div>
        </form>
    </div>
@endsection
