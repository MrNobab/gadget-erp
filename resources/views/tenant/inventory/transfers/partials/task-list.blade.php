<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-x-auto mb-6">
    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
        <h3 class="text-lg font-bold">{{ $title }}</h3>
        <span class="px-2 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">
            {{ $transfers->count() }}
        </span>
    </div>

    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
            <tr>
                <th class="text-left px-4 py-3">Transfer</th>
                <th class="text-left px-4 py-3">From</th>
                <th class="text-left px-4 py-3">To</th>
                <th class="text-left px-4 py-3">Status</th>
                <th class="text-left px-4 py-3">Requested By</th>
                <th class="text-left px-4 py-3">Time</th>
                <th class="text-left px-4 py-3">Actions</th>
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

                    <td class="px-4 py-3">
                        @if($transfer->status === 'requested')
                            {{ $transfer->requested_at?->format('d M Y h:i A') }}
                        @elseif($transfer->status === 'accepted')
                            {{ $transfer->accepted_at?->format('d M Y h:i A') }}
                        @elseif($transfer->status === 'in_transit')
                            {{ $transfer->sent_at?->format('d M Y h:i A') }}
                        @elseif($transfer->status === 'received')
                            {{ $transfer->received_at?->format('d M Y h:i A') }}
                        @else
                            {{ $transfer->updated_at?->format('d M Y h:i A') }}
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('tenant.stock-transfers.show', [$tenant, $transfer->id]) }}" class="px-3 py-1 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold">
                                View
                            </a>

                            @if($actionType === 'warehouse_requested')
                                <form method="POST" action="{{ route('tenant.stock-transfers.accept', [$tenant, $transfer->id]) }}">
                                    @csrf
                                    <input type="hidden" name="note" value="Accepted from warehouse tasks">
                                    <button class="px-3 py-1 rounded-lg bg-green-700 text-white text-xs font-semibold">
                                        Accept
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('tenant.stock-transfers.reject', [$tenant, $transfer->id]) }}" onsubmit="return confirm('Reject this request?')">
                                    @csrf
                                    <input type="hidden" name="note" value="Rejected from warehouse tasks">
                                    <button class="px-3 py-1 rounded-lg bg-red-50 text-red-700 text-xs font-semibold">
                                        Reject
                                    </button>
                                </form>
                            @endif

                            @if($actionType === 'warehouse_accepted')
                                <form method="POST" action="{{ route('tenant.stock-transfers.send', [$tenant, $transfer->id]) }}" onsubmit="return confirm('Send this stock now?')">
                                    @csrf
                                    <input type="hidden" name="note" value="Sent from warehouse tasks">
                                    <button class="px-3 py-1 rounded-lg bg-blue-700 text-white text-xs font-semibold">
                                        Send / In Transit
                                    </button>
                                </form>
                            @endif

                            @if($actionType === 'shop_incoming')
                                <form method="POST" action="{{ route('tenant.stock-transfers.receive', [$tenant, $transfer->id]) }}" onsubmit="return confirm('Confirm stock received?')">
                                    @csrf
                                    <input type="hidden" name="note" value="Received from shop tasks">
                                    <button class="px-3 py-1 rounded-lg bg-slate-900 text-white text-xs font-semibold">
                                        Receive
                                    </button>
                                </form>
                            @endif

                            @if($actionType === 'warehouse_received_unacknowledged')
                                <form method="POST" action="{{ route('tenant.stock-transfers.acknowledge-received', [$tenant, $transfer->id]) }}">
                                    @csrf
                                    <input type="hidden" name="note" value="Warehouse marked received transfer as read">
                                    <button class="px-3 py-1 rounded-lg bg-slate-900 text-white text-xs font-semibold">
                                        Mark As Read
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                        {{ $empty }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
