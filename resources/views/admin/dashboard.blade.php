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
        <h3 class="text-lg font-bold mb-2">Next Build Step</h3>
        <p class="text-slate-600">
            After this dashboard works, we will build tenant creation, plan creation, and license assignment.
        </p>
    </div>
@endsection
