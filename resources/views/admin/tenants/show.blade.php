@extends('layouts.admin')

@section('content')
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">{{ $tenant->name }}</h2>
            <p class="text-slate-500">Tenant details and license information.</p>
        </div>

        <a href="{{ route('admin.tenants.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
            Back
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <h3 class="text-lg font-bold mb-4">Shop</h3>

            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-slate-500">Name</dt>
                    <dd class="font-semibold">{{ $tenant->name }}</dd>
                </div>

                <div>
                    <dt class="text-slate-500">Slug</dt>
                    <dd class="font-semibold">{{ $tenant->slug }}</dd>
                </div>

                <div>
                    <dt class="text-slate-500">Status</dt>
                    <dd class="font-semibold">{{ ucfirst($tenant->status) }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <h3 class="text-lg font-bold mb-4">Owner</h3>

            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-slate-500">Name</dt>
                    <dd class="font-semibold">{{ $tenant->owner_name }}</dd>
                </div>

                <div>
                    <dt class="text-slate-500">Email</dt>
                    <dd class="font-semibold">{{ $tenant->owner_email }}</dd>
                </div>

                <div>
                    <dt class="text-slate-500">Phone</dt>
                    <dd class="font-semibold">{{ $tenant->owner_phone ?: '-' }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <h3 class="text-lg font-bold mb-4">License</h3>

            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-slate-500">Plan</dt>
                    <dd class="font-semibold">{{ $tenant->license?->plan?->name ?? '-' }}</dd>
                </div>

                <div>
                    <dt class="text-slate-500">Status</dt>
                    <dd class="font-semibold">{{ ucfirst($tenant->license?->status ?? 'missing') }}</dd>
                </div>

                <div>
                    <dt class="text-slate-500">Expires</dt>
                    <dd class="font-semibold">{{ $tenant->license?->expires_at?->format('d M Y') ?? '-' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h3 class="text-lg font-bold mb-4">License Logs</h3>

        <div class="overflow-hidden rounded-lg border border-slate-200">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">Date</th>
                        <th class="text-left px-4 py-3">From</th>
                        <th class="text-left px-4 py-3">To</th>
                        <th class="text-left px-4 py-3">Changed By</th>
                        <th class="text-left px-4 py-3">Reason</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tenant->license?->logs ?? [] as $log)
                        <tr>
                            <td class="px-4 py-3">{{ $log->created_at->format('d M Y h:i A') }}</td>
                            <td class="px-4 py-3">{{ $log->from_status ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $log->to_status }}</td>
                            <td class="px-4 py-3">{{ $log->changedBy?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $log->reason ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                                No license logs found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
