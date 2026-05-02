@extends('layouts.admin')

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">Plans</h2>
            <p class="text-slate-500">Manage SaaS license plans.</p>
        </div>

        <a href="{{ route('admin.plans.create') }}" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
            Create Plan
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Name</th>
                    <th class="text-left px-4 py-3">Price</th>
                    <th class="text-left px-4 py-3">Cycle</th>
                    <th class="text-left px-4 py-3">Users</th>
                    <th class="text-left px-4 py-3">Products</th>
                    <th class="text-left px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($plans as $plan)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ $plan->name }}</div>
                            <div class="text-xs text-slate-500">{{ $plan->slug }}</div>
                        </td>
                        <td class="px-4 py-3">৳{{ number_format((float) $plan->price, 2) }}</td>
                        <td class="px-4 py-3">{{ ucfirst($plan->billing_cycle) }}</td>
                        <td class="px-4 py-3">{{ $plan->max_users }}</td>
                        <td class="px-4 py-3">{{ $plan->max_products }}</td>
                        <td class="px-4 py-3">
                            @if($plan->is_active)
                                <span class="px-2 py-1 rounded-full bg-green-50 text-green-700 text-xs font-semibold">Active</span>
                            @else
                                <span class="px-2 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">Inactive</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                            No plans yet. Create your first plan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $plans->links() }}
    </div>
@endsection
