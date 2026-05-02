@extends('layouts.tenant')

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">Shop Tasks</h2>
            <p class="text-slate-500">Receive incoming stock and track shop transfer requests.</p>
        </div>

        <a href="{{ route('tenant.stock-transfers.index', $tenant) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
            All Transfers
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Incoming Transfers To Receive</div>
            <div class="text-3xl font-bold mt-2 text-blue-700">{{ $incomingTransfers->count() }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="text-sm text-slate-500">Requested / Waiting Warehouse</div>
            <div class="text-3xl font-bold mt-2 text-yellow-700">{{ $requestedByShopTransfers->count() }}</div>
        </div>
    </div>

    @include('tenant.inventory.transfers.partials.task-list', [
        'title' => 'Incoming Stock Waiting To Receive',
        'transfers' => $incomingTransfers,
        'empty' => 'No incoming stock waiting to receive.',
        'actionType' => 'shop_incoming',
    ])

    @include('tenant.inventory.transfers.partials.task-list', [
        'title' => 'Shop Requests Waiting For Warehouse',
        'transfers' => $requestedByShopTransfers,
        'empty' => 'No shop requests waiting for warehouse.',
        'actionType' => 'shop_waiting',
    ])
@endsection
