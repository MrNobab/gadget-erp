@extends('layouts.tenant')

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">Stock Transfers</h2>
            <p class="text-slate-500">Request, accept, send, track in-transit stock, and confirm receiving.</p>
        </div>

        <a href="{{ route('tenant.stock-transfers.create', $tenant) }}" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
            Request Transfer
        </a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-4">
        <form method="GET" action="{{ route('tenant.stock-transfers.index', $tenant) }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <select name="status" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">All Statuses</option>
                @foreach($statuses as $itemStatus)
                    <option value="{{ $itemStatus }}" @selected($status === $itemStatus)>
                        {{ ucwords(str_replace('_', ' ', $itemStatus)) }}
                    </option>
                @endforeach
            </select>

            <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Filter
            </button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Transfer</th>
                    <th class="text-left px-4 py-3">From</th>
                    <th class="text-left px-4 py-3">To</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Requested By</th>
                    <th class="text-left px-4 py-3">Requested At</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse($transfers as $transfer)
                    <tr>
                        <td class="px-4 py-3 font-semibold">{{ $transfer->transfer_number }}</td>
                        <td class="px-4 py-3">
                            {{ $transfer->source?->name }}
                            <div class="text-xs text-slate-500">{{ ucwords($transfer->source?->type) }}</div>
                        </td>
                        <td class="px-4 py-3">
                            {{ $transfer->destination?->name }}
                            <div class="text-xs text-slate-500">{{ ucwords($transfer->destination?->type) }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold
                                @if($transfer->status === 'received') bg-green-50 text-green-700
                                @elseif($transfer->status === 'in_transit') bg-blue-50 text-blue-700
                                @elseif($transfer->status === 'requested') bg-yellow-50 text-yellow-700
                                @elseif($transfer->status === 'accepted') bg-purple-50 text-purple-700
                                @else bg-slate-100 text-slate-700
                                @endif
                            ">
                                {{ ucwords(str_replace('_', ' ', $transfer->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ $transfer->requestedBy?->name ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $transfer->requested_at?->format('d M Y h:i A') ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('tenant.stock-transfers.show', [$tenant, $transfer->id]) }}" class="font-semibold hover:underline">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">No transfers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $transfers->links() }}</div>
@endsection
