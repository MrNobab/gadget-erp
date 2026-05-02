@extends('layouts.tenant')

@section('content')
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">{{ $customer->name }}</h2>
            <p class="text-slate-500">Customer profile, due balance, and notes.</p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('tenant.customers.edit', [$tenant, $customer->id]) }}" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                Edit
            </a>

            <a href="{{ route('tenant.customers.index', $tenant) }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">
                Back
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="text-sm text-slate-500">Total Purchases</div>
            <div class="mt-2 text-2xl font-bold">৳{{ number_format((float) $customer->total_purchases, 2) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="text-sm text-slate-500">Total Paid</div>
            <div class="mt-2 text-2xl font-bold">৳{{ number_format((float) $customer->total_paid, 2) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="text-sm text-slate-500">Total Due</div>
            <div class="mt-2 text-2xl font-bold {{ (float) $customer->total_due > 0 ? 'text-red-600' : '' }}">
                ৳{{ number_format((float) $customer->total_due, 2) }}
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="text-sm text-slate-500">Status</div>
            <div class="mt-2 text-2xl font-bold">{{ $customer->is_active ? 'Active' : 'Inactive' }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <h3 class="text-lg font-bold mb-4">Customer Information</h3>

            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-slate-500">Phone</dt>
                    <dd class="font-semibold">{{ $customer->phone }}</dd>
                </div>

                <div>
                    <dt class="text-slate-500">Email</dt>
                    <dd class="font-semibold">{{ $customer->email ?: '-' }}</dd>
                </div>

                <div>
                    <dt class="text-slate-500">City</dt>
                    <dd class="font-semibold">{{ $customer->city ?: '-' }}</dd>
                </div>

                <div>
                    <dt class="text-slate-500">Address</dt>
                    <dd class="font-semibold whitespace-pre-line">{{ $customer->address ?: '-' }}</dd>
                </div>
            </dl>

            <form method="POST" action="{{ route('tenant.customers.destroy', [$tenant, $customer->id]) }}" class="mt-6" onsubmit="return confirm('Delete this customer?')">
                @csrf
                @method('DELETE')

                <button type="submit" class="px-4 py-2 rounded-lg bg-red-50 text-red-700 text-sm font-semibold">
                    Delete Customer
                </button>
            </form>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <h3 class="text-lg font-bold mb-4">Add Note</h3>

                <form method="POST" action="{{ route('tenant.customers.notes.store', [$tenant, $customer->id]) }}" class="space-y-4">
                    @csrf

                    <textarea name="note" rows="3" required class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Write a note about this customer...">{{ old('note') }}</textarea>

                    <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">
                        Save Note
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <h3 class="text-lg font-bold mb-4">Notes</h3>

                <div class="space-y-3">
                    @forelse($customer->notes->sortByDesc('created_at') as $note)
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <div class="text-sm whitespace-pre-line">{{ $note->note }}</div>
                            <div class="mt-2 text-xs text-slate-500">
                                {{ $note->created_at->format('d M Y h:i A') }}
                                by
                                {{ $note->creator?->name ?? 'Unknown' }}
                            </div>
                        </div>
                    @empty
                        <p class="text-slate-500 text-sm">No notes yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <h3 class="text-lg font-bold mb-2">Sales History</h3>
                <p class="text-slate-500 text-sm">
                    Sales, payments, warranty, and ledger history will appear here after we build the POS and ledger modules.
                </p>
            </div>
        </div>
    </div>
@endsection
