@extends('layouts.tenant')

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">{{ $transfer->transfer_number }}</h2>
            <p class="text-slate-500">Transfer details, status, items, and operation logs.</p>
        </div>

        <a href="{{ route('tenant.stock-transfers.index', $tenant) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
            Back
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Status</div>
            <div class="text-xl font-bold mt-2">{{ ucwords(str_replace('_', ' ', $transfer->status)) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">From</div>
            <div class="text-xl font-bold mt-2">{{ $transfer->source?->name }}</div>
            <div class="text-xs text-slate-500">{{ ucwords($transfer->source?->type) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">To</div>
            <div class="text-xl font-bold mt-2">{{ $transfer->destination?->name }}</div>
            <div class="text-xs text-slate-500">{{ ucwords($transfer->destination?->type) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Requested By</div>
            <div class="text-xl font-bold mt-2">{{ $transfer->requestedBy?->name ?? '-' }}</div>
            <div class="text-xs text-slate-500">{{ $transfer->requested_at?->format('d M Y h:i A') }}</div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-6">
        <h3 class="text-lg font-bold mb-4">Actions</h3>

        <div class="flex flex-wrap gap-3">
            @if($transfer->status === 'requested')
                <form method="POST" action="{{ route('tenant.stock-transfers.accept', [$tenant, $transfer->id]) }}">
                    @csrf
                    <input type="hidden" name="note" value="Accepted">
                    <button class="px-4 py-2 rounded-lg bg-green-700 text-white text-sm font-semibold">
                        Accept Request
                    </button>
                </form>

                <form method="POST" action="{{ route('tenant.stock-transfers.reject', [$tenant, $transfer->id]) }}" onsubmit="return confirm('Reject this transfer request?')">
                    @csrf
                    <input type="hidden" name="note" value="Rejected">
                    <button class="px-4 py-2 rounded-lg bg-red-50 text-red-700 text-sm font-semibold">
                        Reject
                    </button>
                </form>
            @endif

            @if($transfer->status === 'accepted')
                <form method="POST" action="{{ route('tenant.stock-transfers.send', [$tenant, $transfer->id]) }}" onsubmit="return confirm('Send this transfer? Stock will be deducted from source and marked in transit.')">
                    @csrf
                    <input type="hidden" name="note" value="Sent to destination">
                    <button class="px-4 py-2 rounded-lg bg-blue-700 text-white text-sm font-semibold">
                        Send Stock / Mark In Transit
                    </button>
                </form>
            @endif

            @if($transfer->status === 'in_transit')
                <form method="POST" action="{{ route('tenant.stock-transfers.receive', [$tenant, $transfer->id]) }}" onsubmit="return confirm('Confirm received? Stock will be added to destination.')">
                    @csrf
                    <input type="hidden" name="note" value="Received by destination">
                    <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                        Confirm Receive
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-6">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Product</th>
                    <th class="text-left px-4 py-3">SKU</th>
                    <th class="text-right px-4 py-3">Quantity</th>
                    <th class="text-right px-4 py-3">Unit Cost</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @foreach($transfer->items as $item)
                    <tr>
                        <td class="px-4 py-3 font-semibold">{{ $item->product?->name }}</td>
                        <td class="px-4 py-3">{{ $item->product?->sku }}</td>
                        <td class="px-4 py-3 text-right">{{ $item->quantity }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $item->unit_cost, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h3 class="text-lg font-bold mb-4">Operation Logs</h3>

        <div class="space-y-3">
            @forelse($transfer->logs->sortByDesc('created_at') as $log)
                <div class="rounded-lg bg-slate-50 border border-slate-200 p-4 text-sm">
                    <div class="font-semibold">{{ ucwords(str_replace('_', ' ', $log->action)) }}</div>
                    <div class="text-slate-500">
                        {{ $log->from_status ?: '-' }} → {{ $log->to_status ?: '-' }}
                    </div>
                    <div class="text-slate-500">
                        By {{ $log->user?->name ?? '-' }} on {{ $log->created_at->format('d M Y h:i A') }}
                    </div>
                    @if($log->notes)
                        <div class="mt-1">{{ $log->notes }}</div>
                    @endif
                </div>
            @empty
                <p class="text-slate-500 text-sm">No logs found.</p>
            @endforelse
        </div>
    </div>
@endsection
