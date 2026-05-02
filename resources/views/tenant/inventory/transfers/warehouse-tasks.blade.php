@extends('layouts.tenant')

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">Warehouse Tasks</h2>
            <p class="text-slate-500">Approve requests, send stock, monitor in-transit stock, and acknowledge received transfers.</p>
        </div>

        <a href="{{ route('tenant.stock-transfers.index', $tenant) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
            All Transfers
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">New Requests</div>
            <div class="text-3xl font-bold mt-2 text-yellow-700">{{ $requestedTransfers->count() }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Ready To Send</div>
            <div class="text-3xl font-bold mt-2 text-purple-700">{{ $acceptedTransfers->count() }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">In Transit</div>
            <div class="text-3xl font-bold mt-2 text-blue-700">{{ $inTransitTransfers->count() }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Need Acknowledgement</div>
            <div class="text-3xl font-bold mt-2 text-red-700">{{ $receivedUnacknowledgedTransfers->count() }}</div>
        </div>
    </div>

    @include('tenant.inventory.transfers.partials.task-list', [
        'title' => 'New Transfer Requests',
        'transfers' => $requestedTransfers,
        'empty' => 'No new transfer requests.',
        'actionType' => 'warehouse_requested',
    ])

    @include('tenant.inventory.transfers.partials.task-list', [
        'title' => 'Accepted Requests Waiting To Send',
        'transfers' => $acceptedTransfers,
        'empty' => 'No accepted transfers waiting to send.',
        'actionType' => 'warehouse_accepted',
    ])

    @include('tenant.inventory.transfers.partials.task-list', [
        'title' => 'In Transit From Warehouse',
        'transfers' => $inTransitTransfers,
        'empty' => 'No in-transit transfers.',
        'actionType' => 'warehouse_in_transit',
    ])

    @include('tenant.inventory.transfers.partials.task-list', [
        'title' => 'Received By Shop - Need Warehouse Acknowledgement',
        'transfers' => $receivedUnacknowledgedTransfers,
        'empty' => 'No received transfers waiting acknowledgement.',
        'actionType' => 'warehouse_received_unacknowledged',
    ])
@endsection
