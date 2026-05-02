@extends('layouts.admin')

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">Tenants</h2>
            <p class="text-slate-500">Manage shops using Gadget ERP.</p>
        </div>

        <a href="{{ route('admin.tenants.create') }}" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
            Create Tenant
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Tenant</th>
                    <th class="text-left px-4 py-3">Owner</th>
                    <th class="text-left px-4 py-3">Plan</th>
                    <th class="text-left px-4 py-3">License</th>
                    <th class="text-left px-4 py-3">Expires</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($tenants as $tenant)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ $tenant->name }}</div>
                            <div class="text-xs text-slate-500">/shop/{{ $tenant->slug }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ $tenant->owner_name }}</div>
                            <div class="text-xs text-slate-500">{{ $tenant->owner_email }}</div>
                        </td>
                        <td class="px-4 py-3">
                            {{ $tenant->license?->plan?->name ?? 'No Plan' }}
                        </td>
                        <td class="px-4 py-3">
                            @php($status = $tenant->license?->status ?? 'missing')

                            <span class="px-2 py-1 rounded-full text-xs font-semibold
                                @if($status === 'active') bg-green-50 text-green-700
                                @elseif($status === 'frozen') bg-yellow-50 text-yellow-700
                                @elseif($status === 'suspended') bg-red-50 text-red-700
                                @elseif($status === 'expired') bg-slate-100 text-slate-700
                                @else bg-red-50 text-red-700
                                @endif
                            ">
                                {{ ucfirst($status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            {{ $tenant->license?->expires_at?->format('d M Y') ?? '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.tenants.show', $tenant) }}" class="text-slate-900 font-semibold hover:underline">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                            No tenants yet. Create your first tenant.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $tenants->links() }}
    </div>
@endsection
